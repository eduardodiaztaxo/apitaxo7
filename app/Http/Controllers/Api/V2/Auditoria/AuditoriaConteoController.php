<?php

namespace App\Http\Controllers\Api\V2\Auditoria;

use App\Http\Controllers\Controller;
use App\Http\Resources\V2\Auditoria\AuditAssetResultResource;
use App\Models\CrudActivo;
use App\Models\InvCiclo;
use App\Rules\SubLevelPlaceRule;
use App\Services\ActivoService;
use App\Services\AuditLabelsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class AuditoriaConteoController extends Controller
{
    //Migration
    //ALTER TABLE `taxochil_ac-lascondes`.`inv_conteo_registro` ADD COLUMN `codigo_ubicacion` VARCHAR(16) NULL AFTER `punto_id`, ADD COLUMN `sublevel` SMALLINT(2) NULL AFTER `codigo_ubicacion`; 


    /**
     * Display the specified resource.
     * @param \Illuminate\Http\Request $request
     * @param  int  $ciclo
     * @param  int  $punto
     * @return \Illuminate\Http\Response
     * @param string $codigo
     * @param int $subnivel
     * 
     * @return \Illuminate\Http\Response
     * 
     * Ejemplo de código de ubicación: "010203" con subnivel 3, o "0102" con subnivel 2, o "01" con subnivel 1. Si el código es "0" o vacío, se omite este filtro y se consideran todas las ubicaciones.
     */
    public function showResumen(
        int $ciclo,
        int $punto,
        string $codigo,
        int $subnivel,
        Request $request
    ) {

        $isGlobalAudit = $request->global === 1 || $request->global === '1';

        $this->validateCodigoSubnivel($request, $codigo, $subnivel);

        $cicloObj = InvCiclo::find($ciclo);

        if (!$cicloObj) {
            return response()->json(['status' => 'error', 'code' => 404], 404);
        }


        $processedTags = AuditLabelsService::getProcessedTagsData_FromDB(
            $ciclo,
            $punto,
            $codigo,
            $subnivel,
            $isGlobalAudit
        );

        //Etiquetas que debieran estar
        $tags = ActivoService::getTagsByCycleAndAnyPlace($cicloObj, $punto, $codigo, $subnivel, $isGlobalAudit);

        //etiquetas encontradas en el conteo, que se corresponden con las que debían estar
        $foundTags = $request->foundTags ?? [];

        $auditLabelServ = new AuditLabelsService($foundTags, $tags->toArray(), $processedTags);


        $resumen = $auditLabelServ->getResumen();


        return response()->json([
            'status' => 'OK',
            'data' => $resumen
        ]);
    }

    /**
     * Display the specified resource.
     * 
     * Show assets results of the audit, including found, missing and sobrante assets with their details. this take into account partial data
     * @param \Illuminate\Http\Request $request
     * @param  int  $ciclo
     * @param  int  $punto
     * @return \Illuminate\Http\Response
     * @param string $codigo
     * @param int $subnivel
     * 
     * @return \Illuminate\Http\Response
     * 
     * Ejemplo de código de ubicación: "010203" con subnivel 3, o "0102" con subnivel 2, o "01" con subnivel 1. Si el código es "0" o vacío, se omite este filtro y se consideran todas las ubicaciones.
     */
    public function showAssetsResults(
        int $ciclo,
        int $punto,
        string $codigo,
        int $subnivel,
        Request $request
    ) {

        $isGlobalAudit = $request->global === 1 || $request->global === '1';
        // Similar a showResumen, pero en lugar de devolver solo el resumen, devuelve los activos encontrados, faltantes y sobrantes con su información detallada.
        // Se puede reutilizar la lógica de showResumen para obtener las etiquetas coincidentes, faltantes y sobrantes, y luego hacer consultas adicionales para obtener la información de cada activo.

        $this->validateCodigoSubnivel($request, $codigo, $subnivel);

        $cicloObj = InvCiclo::find($ciclo);

        if (!$cicloObj) {
            return response()->json(['status' => 'error', 'code' => 404], 404);
        }


        //registros parciales del conteo, mismo u otro usuario, que se corresponden con el ciclo, punto y ubicación (si se especifica código de ubicación)
        $processedTags = AuditLabelsService::getProcessedTagsData_FromDB(
            $ciclo,
            $punto,
            $codigo,
            $subnivel,
            $isGlobalAudit
        );

        //Etiquetas que debieran estar
        $tags = ActivoService::getTagsByCycleAndAnyPlace($cicloObj, $punto, $codigo, $subnivel, $isGlobalAudit);

        //etiquetas encontradas en el conteo, que se corresponden con las que debían estar
        $foundTags = $request->foundTags ?? [];

        $auditLabelServ = new AuditLabelsService($foundTags, $tags->toArray(), $processedTags);

        $audit_assets_result = collect($auditLabelServ->getAuditListDetail());

        /**
         * 1.- Si no hay palabra clave, se filtra por from and rows 
         * 2.- Si hay palabra clave, se buscan en la DB sin paginar, y se pasan como filtro a AuditService 
         * 3.- Se pasan las etiquetas encontradas y se devuelven resultados paginados 
         */

        if (!!keyword_is_searcheable($request->keyword ?? '')) {

            $sobrantes = $audit_assets_result->where('status', CrudActivo::AUDIT_STATUS_SOBRANTE);

            //Se consulta todo pero sin paginar
            $queryBuilder = CrudActivo::queryBuilderAsset_Audit_ConfigCycle_FindInAddressGroupFamily_Pagination(
                $cicloObj,
                $punto,
                '',
                0,
                $request->keyword ?? '',
                0,
                0
            );


            //Está en el lugar o está como sobrante pero en otro lugar
            $etiquetas_aceptadas = $queryBuilder->orWhere(function ($query) use ($sobrantes) {
                $query->whereIn('crud_activos.etiqueta', $sobrantes->pluck('etiqueta')->toArray());
            })->get()->pluck('etiqueta')->toArray();

            //Si la busqueda es una etiqueta sobrante que no se encuentra en el lugar,
            //Ni en ningún otro lugar 
            //también la aceptamos para mostrar su resultado aunque no se encuentre en ningún lugar
            $sobrante_etiquetas = $sobrantes->where('etiqueta', $request->keyword)->pluck('etiqueta')->toArray();

            $etiquetas_aceptadas = array_unique(array_merge($etiquetas_aceptadas, $sobrante_etiquetas));

            $audit_assets_result_pagination = $auditLabelServ->getAuditListDetail_Filter_Pagination($etiquetas_aceptadas, $request->from ?? 0, $request->rows ?? 0);
        } else {

            $audit_assets_result_pagination = $auditLabelServ->getAuditListDetail_Filter_Pagination([], $request->from ?? 0, $request->rows ?? 0);
        }
        /**
         * 1.- Recibe etiquetas encontradas, busca la lista de etiquetas del lugar y obtiene etiquetas procesadas desde DB.
         * 2.- Determina cuáles etiquetas son coincidentes, faltantes y sobrantes.
         * 3.- Busca una porción de los activos del lugar (paginacion) y devuelve su estado de auditoría (coincidente o faltante)
         * 4.- Si no hay más activos que devolver, devuelve los sobrantes
         * 5.- Los sobrantes pueden ser activos existentes o no existentes
         * 
         */




        return response()->json([
            'status' => 'OK',
            'data' => AuditAssetResultResource::collection($audit_assets_result_pagination),
            //'sql' => $queryBuilder->toSql(),
            //'bindings' => $queryBuilder->getBindings(),
        ]);
    }


    /** 
     * Display tags to audit by place (address or sub level).
     *
     * @param   int $ciclo 
     * @param   int $punto
     * @param   string $codigo
     * @param   int $subnivel
     * @param   \Illuminate\Http\Request
     * @return  \Illuminate\Http\Response
     */
    public function showOnlyTagsToAuditByPlace(
        int $ciclo,
        int $punto,
        string $codigo,
        int $subnivel,
        Request $request
    ) {

        $isGlobalAudit = $request->global === 1 || $request->global === '1';

        $this->validateCodigoSubnivel($request, $codigo, $subnivel);

        $cicloObj = InvCiclo::find($ciclo);

        if (!$cicloObj) {
            return response()->json(['status' => 'error', 'code' => 404], 404);
        }



        //Se consulta todo pero sin paginar


        $tags = ActivoService::getTagsByCycleAndAnyPlace($cicloObj, $punto, $codigo, $subnivel, $isGlobalAudit);




        return response()->json([
            'status' => 'OK',
            'data' => $tags->toArray(),
        ]);
    }

    /**
     * Process the counted tags for a specific cycle, point, and location (if location code is specified). 
     * This will update the audit records in the database based on the provided found tags and the expected tags for that location.
     * it take account partial data, so if there are already records for that cycle, point and location, it will update them instead of creating new ones, and if there are new tags it will create new records.
     * @param int $ciclo
     * @param int $punto
     * @param string $codigo
     * @param int $subnivel
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function processTags(
        int $ciclo,
        int $punto,
        string $codigo,
        int $subnivel,
        Request $request
    ) {


        $isGlobalAudit = $request->global === 1 || $request->global === '1';

        // Similar a showResumen, pero en lugar de devolver solo el resumen, devuelve los activos encontrados, faltantes y sobrantes con su información detallada.
        // Se puede reutilizar la lógica de showResumen para obtener las etiquetas coincidentes, faltantes y sobrantes, y luego hacer consultas adicionales para obtener la información de cada activo.

        $this->validateCodigoSubnivel($request, $codigo, $subnivel);

        $cicloObj = InvCiclo::find($ciclo);

        if (!$cicloObj) {
            return response()->json(['status' => 'error', 'code' => 404], 404);
        }


        //registros parciales del conteo, mismo u otro usuario, que se corresponden con el ciclo, punto y ubicación (si se especifica código de ubicación)
        $processedTags = AuditLabelsService::getProcessedTagsData_FromDB(
            $ciclo,
            $punto,
            $codigo,
            $subnivel,
            $isGlobalAudit
        );

        //Etiquetas que debieran estar
        $tags = ActivoService::getTagsByCycleAndAnyPlace($cicloObj, $punto, $codigo, $subnivel, $isGlobalAudit);

        //etiquetas encontradas en el conteo, que se corresponden con las que debían estar
        $foundTags = $request->foundTags ?? [];

        $auditLabelServ = new AuditLabelsService($foundTags, $tags->toArray(), $processedTags);

        $audit_assets_result = collect($auditLabelServ->getAuditListDetail());



        foreach ($audit_assets_result as $key => $item) {



            $validator = Validator::make((array)$item, $this->rules());

            if ($validator->fails()) {

                $errors[] = ['index' => $key, 'errors' => $validator->errors()->get("*")];
            } else if (empty($errors)) {

                $activo = [
                    'ciclo_id'          => $cicloObj->idCiclo,
                    'punto_id'          => $punto,
                    'etiqueta'          => $item['etiqueta'],
                    'audit_status'      => $item['audit_status'],
                    'codigo_ubicacion'  => $codigo,
                    'sublevel'          => $subnivel,
                    'global_general'    => $isGlobalAudit ? 1 : 0,
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
                    WHERE ciclo_id = ? AND punto_id = ? AND codigo_ubicacion = ? AND sublevel = ?',
            [$cicloObj->idCiclo, $punto, $codigo, $subnivel]
        );

        //se insertan los nuevos registros
        DB::table('inv_conteo_registro')->insert($assets);


        return response()->json([
            'status' => 'OK',
            'code'   => 200,
            'message' => 'successfully processed'
        ]);
    }

    /**
     * This method deletes all audit records that not match for the specified cycle, point, and location (if location code is specified), 
     * Elimina sobrantes.
     *
     * @param int $ciclo
     * @param int $punto
     * @param string $codigo
     * @param int $subnivel
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function deleteSobrantes(
        int $ciclo,
        int $punto,
        string $codigo,
        int $subnivel,
        Request $request
    ) {

        $isGlobalAudit = $request->global === 1 || $request->global === '1';
        // Elimina los registros de conteo que están marcados como sobrantes para el ciclo, punto y ubicación especificados.

        $this->validateCodigoSubnivel($request, $codigo, $subnivel);

        $cicloObj = InvCiclo::find($ciclo);

        if (!$cicloObj) {
            return response()->json(['status' => 'error', 'code' => 404], 404);
        }

        DB::delete(
            'DELETE FROM inv_conteo_registro 
                    WHERE ciclo_id = ? AND punto_id = ? AND codigo_ubicacion = ? AND sublevel = ? AND audit_status = ?',
            [$ciclo, $punto, $codigo, $subnivel, CrudActivo::AUDIT_STATUS_SOBRANTE]
        );

        return response()->json([
            'status' => 'OK',
            'code'   => 200,
            'message' => 'Sobrantes eliminados correctamente',
            'data' => ActivoService::getTagsByCycleAndAnyPlace($cicloObj, $punto, $codigo, $subnivel, $isGlobalAudit),
        ]);
    }

    /**
     * This method deletes all audit records for the specified cycle, point, and location (if location code is specified), regardless of their status. This can be used to reset the audit for that location.
     * 
     * @param int $ciclo
     * @param int $punto
     * @param string $codigo
     * @param int $subnivel
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function resetDeleteAuditoria(
        int $ciclo,
        int $punto,
        string $codigo,
        int $subnivel,
        Request $request
    ) {
        // Elimina todos los registros de conteo para el ciclo, punto y ubicación especificados, independientemente de su estado.

        $this->validateCodigoSubnivel($request, $codigo, $subnivel);

        $cicloObj = InvCiclo::find($ciclo);

        if (!$cicloObj) {
            return response()->json(['status' => 'error', 'code' => 404], 404);
        }

        DB::update(
            'UPDATE inv_conteo_registro 
                    SET status = 2, updated_at = NOW() , user_id = ?
                    WHERE ciclo_id = ? AND punto_id = ? AND codigo_ubicacion = ? AND sublevel = ?',
            [$request->user()->id, $ciclo, $punto, $codigo, $subnivel]
        );

        return response()->json([
            'status' => 'OK',
            'code'   => 200,
            'message' => 'Registros de auditoría eliminados correctamente'
        ]);
    }

    protected function validateCodigoSubnivel(Request $request,  string $codigo, int $subnivel)
    {
        $request->merge([
            'codigo' => $codigo,
            'subnivel' => $subnivel,
        ]);



        $request->validate([
            'codigo' => ['required', 'string', new SubLevelPlaceRule($request->subnivel)],
            'subnivel' => ['required', 'integer'],
        ]);
    }

    protected function rules()
    {

        return [

            'etiqueta'      => 'required|string',
        ];
    }
}
