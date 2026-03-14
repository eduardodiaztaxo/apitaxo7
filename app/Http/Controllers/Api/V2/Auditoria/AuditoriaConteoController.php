<?php

namespace App\Http\Controllers\Api\V2\Auditoria;

use App\Http\Controllers\Controller;
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


        $queryBuilder = DB::table("inv_conteo_registro")
            ->where('status', '=', 1)
            ->where('ciclo_id', '=', $ciclo)
            ->where('punto_id', '=', $punto);


        if (!in_array($codigo, ['0', '']) && strlen($codigo) > 1 && $subnivel > 0) {
            $queryBuilder = $queryBuilder->where('codigo_ubicacion', '=', $codigo)
                ->where('sublevel', '=', $subnivel)
                ->get();
        }

        //registros parciales del conteo, mismo u otro usuario, que se corresponden con el ciclo, punto y ubicación (si se especifica código de ubicación)
        $processedTags = $queryBuilder->get();

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
}
