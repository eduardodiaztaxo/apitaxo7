<?php

namespace App\Http\Controllers\Api\V1\Auditoria;

use App\Http\Controllers\Controller;
use App\Models\Emplazamiento;
use App\Models\InvCicloPunto;
use App\Services\ActivoService;
use App\Services\AuditLabelsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class InventarioConteoController extends Controller
{
    //
    public function processConteo(Request $request)
    {


        if ($request->items) {
            $request->merge(['items' => json_encode($request->items)]);
        }


        $request->validate([
            'items'   => 'required|json',
            'idCiclo' => 'required|integer|exists:inv_ciclos,idCiclo',
            'idPunto' => 'required|integer|exists:ubicaciones_geograficas,idUbicacionGeo',
        ]);


        $cicloPunto = InvCicloPunto::where('idCiclo', $request->idCiclo)->where('idPunto', $request->idPunto)->first();

        if (!$cicloPunto) {
            return request()->json([
                'status' => 'error',
                'code' => 404,
                'message' => 'La dirección no está asociada al ciclo'
            ], 404);
        }


        $items = json_decode($request->items);

        $assets = [];
        $errors = [];

        foreach ($items as $key => $item) {

            if (isset($item->adicionales)) {
                $item->adicionales = json_encode($item->adicionales);
            }

            $validator = Validator::make((array)$item, $this->rules());

            if ($validator->fails()) {

                $errors[] = ['index' => $key, 'errors' => $validator->errors()->get("*")];
            } else if (empty($errors)) {

                $activo = [
                    'idCiclo'               => $request->idCiclo,
                    'idPunto'               => $request->idPunto,
                    'etiqueta'              => $item->etiqueta,
                    'organica1'             => '0',
                    'organica2'             => '0',
                    'organica3'             => '0',
                    'descripcionUsuario'    => $request->user()->name,
                    // 'created_at'        => date('Y-m-d H:i:s'),
                    // 'updated_at'        => date('Y-m-d H:i:s'),
                ];

                $assets[] = $activo;
            }
        }

        dd($assets);
        DB::table('inv_conteo_det')->insert($assets);


        return response()->json([
            'status' => 'OK',
            'code'   => 200,
            'message' => 'successfully processed'
        ]);
    }

    /**
     * Process count by Emplazamiento
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function processConteoByEmplazamiento(Request $request)
    {




        if ($request->items) {
            $request->merge(['items' => json_encode($request->items)]);
        }


        $request->validate([
            'items'             => 'required|json',
            'ciclo_id'          => 'required|integer|exists:inv_ciclos,idCiclo',
            'emplazamiento_id'  => 'required|integer|exists:ubicaciones_n2,idUbicacionN2',
        ]);


        $empObj = Emplazamiento::find($request->emplazamiento_id);

        $zonaObj = $empObj->zonaPunto;

        $cicloPunto = InvCicloPunto::where('idCiclo', $request->ciclo_id)->where('idPunto', $zonaObj->idAgenda)->first();

        if (!$cicloPunto) {
            return response()->json([
                'status' => 'error',
                'code' => 404,
                'message' => 'La dirección no está asociada al ciclo ' . $zonaObj->idAgenda
            ], 404);
        }


        $items = json_decode($request->items);

        $cicloObj = $cicloPunto->ciclo;

        $etiquetas = ActivoService::getLabelsByCycleAndEmplazamiento($empObj, $cicloObj);



        $assets = [];
        $errors = [];

        $items = collect($items)->unique();



        $auditLabelServ = new AuditLabelsService($items->pluck('etiqueta')->toArray(), $etiquetas->toArray());



        foreach ($auditLabelServ->getAuditListDetail() as $key => $item) {



            $validator = Validator::make((array)$item, $this->rules());

            if ($validator->fails()) {

                $errors[] = ['index' => $key, 'errors' => $validator->errors()->get("*")];
            } else if (empty($errors)) {

                $activo = [
                    'ciclo_id'          => $request->ciclo_id,
                    'punto_id'          => $zonaObj->idAgenda,
                    'etiqueta'          => $item['etiqueta'],
                    'audit_status'      => $item['audit_status'],
                    'cod_zona'          => $zonaObj->codigoUbicacion,
                    'cod_emplazamiento' => $empObj->codigoUbicacion,
                    'user_id'           => $request->user()->id,
                    'created_at'        => date('Y-m-d H:i:s'),
                    'updated_at'        => date('Y-m-d H:i:s'),
                ];

                $assets[] = $activo;
            }
        }

        //quedan los anteriores obsoletos
        DB::update(
            'UPDATE inv_conteo_registro 
                    SET status = 2, updated_at = NOW() 
                    WHERE ciclo_id = ? AND punto_id = ? AND cod_emplazamiento = ? ',
            [$request->ciclo_id, $zonaObj->idAgenda, $empObj->codigoUbicacion]
        );

        //se insertan los nuevos registros
        DB::table('inv_conteo_registro')->insert($assets);


        return response()->json([
            'status' => 'OK',
            'code'   => 200,
            'message' => 'successfully processed'
        ]);
    }

    protected function rules()
    {

        return [

            'etiqueta'      => 'required|string',
        ];
    }
}
