<?php

namespace App\Http\Controllers\Api\V2\Auditoria;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\CrudActivoLiteResource;
use App\Http\Resources\V1\GroupFamilyPlaceResumenResource;
use App\Models\CrudActivo;
use App\Models\InvCiclo;
use Illuminate\Http\Request;

class CiclosAuditoriaController extends Controller
{
    /**
     * Display assets of the specified resource.
     *
     * @param   int $ciclo 
     * @param   int $punto
     * @param   \Illuminate\Http\Request
     * @return  \Illuminate\Http\Response
     */
    public function showAssetsByCycle(int $ciclo, Request $request)
    {

        $cicloObj = InvCiclo::find($ciclo);

        if (!$cicloObj) {
            return response()->json(['status' => 'error', 'code' => 404], 404);
        }





        $queryBuilder = CrudActivo::queryBuilderAsset_Audit_ConfigCycle_FindInAddressGroupFamily_Pagination(
            $cicloObj,
            0,
            '',
            0,
            $request->keyword ?? '',
            $request->from ?? 0,
            $request->rows ?? 0
        );

        $assets = $queryBuilder->get();

        //
        return response()->json([
            'status' => 'OK',
            'data' => CrudActivoLiteResource::collection($assets),
            // 'sql' => $queryBuilder->toSql(),
            // 'bindings' => $queryBuilder->getBindings()
        ]);
    }





    /**
     * Display families of the specified resource.
     *
     * @param   int $ciclo 
     * @param   int $punto
     * @param   \Illuminate\Http\Request
     * @return  \Illuminate\Http\Response
     */
    public function showGroupFamilies(int $ciclo, Request $request)
    {




        $cicloObj = InvCiclo::find($ciclo);

        if (!$cicloObj) {
            return response()->json(['status' => 'error', 'code' => 404], 404);
        }

        $queryBuilder = $cicloObj->crud_audit_group_families();

        $family_place_resumen = $queryBuilder->get();



        //
        return response()->json([
            'status' => 'OK',
            'data' => GroupFamilyPlaceResumenResource::make($family_place_resumen)
        ]);
    }
}
