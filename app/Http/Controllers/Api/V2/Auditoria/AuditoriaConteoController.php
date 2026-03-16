<?php

namespace App\Http\Controllers\Api\V2\Auditoria;

use App\Http\Controllers\Controller;
use App\Http\Resources\V2\Auditoria\AuditAssetResultResource;
use App\Models\CrudActivo;
use App\Models\InvCiclo;
use App\Services\ActivoService;
use App\Services\AuditLabelsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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

        if (strlen($codigo) > 1 && $subnivel > 0) {
            if (strlen($codigo) / 2 !== $subnivel) {
                response()->json([
                    'status' => 'error',
                    'code'   => 422,
                    'message' => "Si el código tiene una longitud de " . strlen($codigo) . ", su longitud debe ser " . (strlen($codigo) / 2) . " "
                ]);
            }
        }

        $cicloObj = InvCiclo::find($ciclo);

        if (!$cicloObj) {
            return response()->json(['status' => 'error', 'code' => 404], 404);
        }


        $processedTags = AuditLabelsService::getProcessedTagsData_FromDB(
            $ciclo,
            $punto,
            $codigo,
            $subnivel
        );

        //Etiquetas que debieran estar
        $tags = ActivoService::getTagsByCycleAndAnyPlace($cicloObj, $punto, $codigo, $subnivel);

        //etiquetas encontradas en el conteo, que se corresponden con las que debían estar
        $foundTags = $request->foundTags ?? [];

        $auditLabelServ = new AuditLabelsService($foundTags, $tags->toArray(), $processedTags);


        $resumen = $auditLabelServ->getResumen();


        return response()->json([
            'status' => 'OK',
            'data' => $resumen
        ]);
    }

    public function showAssetsResults(
        int $ciclo,
        int $punto,
        string $codigo,
        int $subnivel,
        Request $request
    ) {
        // Similar a showResumen, pero en lugar de devolver solo el resumen, devuelve los activos encontrados, faltantes y sobrantes con su información detallada.
        // Se puede reutilizar la lógica de showResumen para obtener las etiquetas coincidentes, faltantes y sobrantes, y luego hacer consultas adicionales para obtener la información de cada activo.

        if (strlen($codigo) > 1 && $subnivel > 0) {
            if (strlen($codigo) / 2 !== $subnivel) {
                response()->json([
                    'status' => 'error',
                    'code'   => 422,
                    'message' => "Si el código tiene una longitud de " . strlen($codigo) . ", su longitud debe ser " . (strlen($codigo) / 2) . " "
                ]);
            }
        }

        $cicloObj = InvCiclo::find($ciclo);

        if (!$cicloObj) {
            return response()->json(['status' => 'error', 'code' => 404], 404);
        }


        //registros parciales del conteo, mismo u otro usuario, que se corresponden con el ciclo, punto y ubicación (si se especifica código de ubicación)
        $processedTags = AuditLabelsService::getProcessedTagsData_FromDB(
            $ciclo,
            $punto,
            $codigo,
            $subnivel
        );

        //Etiquetas que debieran estar
        $tags = ActivoService::getTagsByCycleAndAnyPlace($cicloObj, $punto, $codigo, $subnivel);

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
            $etiquetas_aceptadas = $queryBuilder->orWhere('etiqueta', $sobrantes->pluck('etiqueta')->toArray())->get()->pluck('etiqueta')->toArray();

            //Si la busqueda es una etiqueta sobrante que no se encuentra en el lugar, también la aceptamos para mostrar su resultado aunque no se encuentre en el lugar
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
        int $subnivel
    ) {

        if (strlen($codigo) > 1 && $subnivel > 0) {
            if (strlen($codigo) / 2 !== $subnivel) {
                response()->json([
                    'status' => 'error',
                    'code'   => 422,
                    'message' => "Si el código tiene una longitud de " . strlen($codigo) . ", su longitud debe ser " . (strlen($codigo) / 2) . " "
                ]);
            }
        }

        $cicloObj = InvCiclo::find($ciclo);

        if (!$cicloObj) {
            return response()->json(['status' => 'error', 'code' => 404], 404);
        }



        //Se consulta todo pero sin paginar
        $queryBuilder = CrudActivo::queryBuilderAsset_Audit_ConfigCycle_FindInAddressGroupFamily_Pagination(
            $cicloObj,
            $punto,
            $codigo,
            $subnivel,
            '',
            0,
            0
        );




        return response()->json([
            'status' => 'OK',
            'data' => $queryBuilder->get()->pluck('etiqueta')->toArray(),
        ]);
    }
}
