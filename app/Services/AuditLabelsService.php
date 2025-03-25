<?php

namespace App\Services;

use App\Models\CrudActivo;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class AuditLabelsService
{
    private array $uniques = [];
    private array $currentLabels;
    private array $initialLabels;
    private Collection $processedLabels;

    public function __construct(array $foundLabels, array $initialLabels, $processedLabels = new Collection([]))
    {


        $this->processedLabels = $processedLabels;

        //quitar faltantes
        $storedLabels = $this->processedLabels->where('audit_status', '<>', 2)->pluck('etiqueta')->toArray();

        //Etiquetas encontradas y procesadas
        $this->currentLabels = array_merge($foundLabels, $storedLabels);



        //Etiquetas iniciales
        $this->initialLabels = $initialLabels;

        $this->uniques = $this->removeDuplicates($this->currentLabels);
    }

    public function getResumen(): array
    {
        $result = $this->getAuditListDetailGroupByAuditStatus();

        return [
            'coincidentes' => count($result['coincidentes']),
            'faltantes'    => count($result['faltantes']),
            'sobrantes'    => count($result['sobrantes']),
        ];
    }

    public function getLabelAuditStatus(string $label)
    {
        $status = 'ninguno';

        if ($this->isSobrante($label)) {
            $status = 'sobrante';
        } else if ($this->isCoincidente($label)) {
            $status = 'coincidente';
        } else {
            $status = 'faltante';
        }

        return $status;
    }

    public function getAuditListDetail(): array
    {
        $labels = [];

        foreach ($this->initialLabels as $element) {
            if ($this->isCoincidente($element)) {
                $labels[] = ['etiqueta' => $element, 'audit_status' => CrudActivo::AUDIT_STATUS_COINCIDENTE];
            } else {
                $labels[] = ['etiqueta' => $element, 'audit_status' => CrudActivo::AUDIT_STATUS_FALTANTE];
            }
        }

        foreach ($this->uniques as $element) {
            if ($this->isSobrante($element)) {
                $labels[] = ['etiqueta' => $element, 'audit_status' => CrudActivo::AUDIT_STATUS_SOBRANTE];
            }
        }

        return $labels;
    }


    public function getAuditListAndActionDetail(): array
    {
        $labels = $this->getAuditListDetail();

        foreach ($labels as $key => $label) {
            $item = $this->processedLabels->where('etiqueta', $label['etiqueta'])->first();

            $action = !$item ? 'insert' : ($item->audit_status === $label['audit_status'] ? 'nothing' : 'update');

            $labels[$key]['action'] = $action;
        }

        return $labels;
    }

    /**
     * process labels and save in DB by Emplazamiento
     * 
     * @param int       $ciclo_id
     * @param int       $punto_id
     * @param string    $cod_zona
     * @param string    $cod_emplazamiento
     * @param int       $user_id
     * @return array
     */
    public function processAuditedLabels_Emplazamiento($ciclo_id, $punto_id, $cod_zona, $cod_emplazamiento, $user_id)
    {
        return $this->processAndSaveAuditedLabels($ciclo_id, $punto_id, $cod_zona, $cod_emplazamiento, $user_id);
    }

    /**
     * process labels and save in DB by Zone
     * 
     * @param int       $ciclo_id
     * @param int       $punto_id
     * @param string    $cod_zona
     * @param int       $user_id
     * @return array
     */
    public function processAuditedLabels_Zone($ciclo_id, $punto_id, $cod_zona, $user_id)
    {
        return $this->processAndSaveAuditedLabels($ciclo_id, $punto_id, $cod_zona, null, $user_id);
    }


    /**
     * process labels and save in DB by Address
     * 
     * @param int       $ciclo_id
     * @param int       $punto_id
     * @param int       $user_id
     * @return array
     */
    public function processAuditedLabels_Address($ciclo_id, $punto_id, $user_id)
    {
        return $this->processAndSaveAuditedLabels($ciclo_id, $punto_id, null, null, $user_id);
    }

    /**
     * process labels and save in DB
     * 
     * @param int       $ciclo_id
     * @param int       $punto_id
     * @param string    $cod_zona
     * @param string    $cod_emplazamiento
     * @param int       $user_id
     * @return array
     */
    private function processAndSaveAuditedLabels($ciclo_id, $punto_id, $cod_zona, $cod_emplazamiento, $user_id)
    {


        $assets_insert = [];
        $assets_update = [];

        $errors = [];

        $resultLabels = collect($this->getAuditListAndActionDetail());



        foreach ($resultLabels->whereIn('action', ['insert', 'update']) as $key => $item) {



            $validator = Validator::make((array)$item, $this->rules());

            if ($validator->fails()) {

                $errors[] = ['etiqueta' => $item->etiqueta, 'errors' => $validator->errors()->get("*")];
            } else if (empty($errors)) {

                if ($item['action'] === 'insert') {

                    $activo = [
                        'ciclo_id'          => $ciclo_id,
                        'punto_id'          => $punto_id,
                        'etiqueta'          => $item['etiqueta'],
                        'audit_status'      => $item['audit_status'],
                        'cod_zona'          => $cod_zona,
                        'cod_emplazamiento' => $cod_emplazamiento,
                        'user_id'           => $user_id,
                        'created_at'        => date('Y-m-d H:i:s'),
                        'updated_at'        => date('Y-m-d H:i:s'),
                    ];

                    $assets_insert[] = $activo;
                } else if ($item['action'] === 'update') {

                    $activo = [
                        'ciclo_id'          => $ciclo_id,
                        'punto_id'          => $punto_id,
                        'etiqueta'          => $item['etiqueta'],
                        'audit_status'      => $item['audit_status'],
                        'cod_zona'          => $cod_zona,
                        'cod_emplazamiento' => $cod_emplazamiento,
                        'user_id'           => $user_id,
                        'updated_at'        => date('Y-m-d H:i:s'),
                    ];

                    $assets_update[] = $activo;
                }
            }
        }


        if (!empty($errors)) {
            return [
                'status' => 'error',
                'errors' => $errors,
            ];
        }





        //se insertan los nuevos registros
        DB::table('inv_conteo_registro')->insert($assets_insert);


        foreach ($assets_update as $item) {
            DB::update("UPDATE inv_conteo_registro 
            SET audit_status = ?, user_id = ?, updated_at = ? 
            WHERE ciclo_id = ? AND punto_id = ? AND etiqueta = ? AND status = 1 ", [
                $item['audit_status'],
                $item['user_id'],
                $item['updated_at'],
                $item['ciclo_id'],
                $item['punto_id'],
                $item['etiqueta']
            ]);
        }

        return [
            'status' => 'OK',
            'errors' => $errors,
        ];
    }



    private function getAuditListDetailGroupByAuditStatus(): array
    {
        $coincidentes = [];
        $faltantes = [];
        $sobrantes = [];

        foreach ($this->initialLabels as $element) {
            if ($this->isCoincidente($element)) {
                $coincidentes[] = $element;
            } else {
                $faltantes[] = $element;
            }
        }

        foreach ($this->uniques as $element) {
            if ($this->isSobrante($element)) {
                $sobrantes[] = $element;
            }
        }

        return [
            'coincidentes' => $coincidentes,
            'faltantes'    => $faltantes,
            'sobrantes'    => $sobrantes,
        ];
    }

    private function isCoincidente(string $element): bool
    {
        return in_array($element, $this->uniques, true);
    }

    private function isSobrante(string $element): bool
    {
        return !in_array($element, $this->initialLabels, true);
    }

    private function removeDuplicates(array $arr): array
    {
        $unique = [];

        foreach ($arr as $element) {
            if (!in_array($element, $unique, true)) {
                $unique[] = $element;
            }
        }

        return $unique;
    }

    protected function rules()
    {

        return [

            'etiqueta'      => 'required|string',
        ];
    }
}
