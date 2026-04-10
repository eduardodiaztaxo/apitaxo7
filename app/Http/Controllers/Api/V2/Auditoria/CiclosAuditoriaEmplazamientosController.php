<?php

namespace App\Http\Controllers\Api\V2\Auditoria;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\CrudActivoLiteResource;
use App\Http\Resources\V1\EmplazamientoNivel1Resource;
use App\Http\Resources\V1\GroupFamilyPlaceResumenResource;
use App\Http\Resources\V2\Auditoria\EmplazamientoNnResource;
use App\Models\Auditoria\EmplazamientoNn;
use App\Models\CrudActivo;
use App\Models\InvCiclo;
use App\Models\UbicacionGeografica;
use App\Rules\SubLevelPlaceRule;
use Illuminate\Http\Request;

class CiclosAuditoriaEmplazamientosController extends Controller
{
    //
    /**
     * Display the specified resource.
     *
     * @param \Illuminate\Http\Request $request
     * @param  int  $ciclo
     * @return \Illuminate\Http\Response
     */
    public function showByCycleAndGrupFamily(Request $request, int $ciclo, int $punto, string $codigo, int $subnivel)
    {
        $this->validateCodigoSubnivel($request, $codigo, $subnivel);

        $cycleObj = InvCiclo::find($ciclo);

        if (!$cycleObj) {
            return response()->json(['status' => 'NOK', 'code' => 404], 404);
        }

        $queryBuilder = EmplazamientoNn::fromTable('ubicaciones_n' . $subnivel)->where('idAgenda', '=', $punto);

        if ($subnivel > 1) {
            $queryBuilder = $queryBuilder->where('codigoUbicacion', 'like', $codigo . '%');
        }

        $emplazamientos = $queryBuilder->get();

        $resources = $emplazamientos->map(function ($emplazamiento) use ($cycleObj, $subnivel) {
            return new EmplazamientoNnResource($emplazamiento, $cycleObj, $subnivel);
        });



        return response()->json(['status' => 'OK', 'data' => $resources], 200);
    }

    public function showAssetsByUbicacionAndSublevel(int $ciclo, int $punto, string $codigo, int $subnivel, Request $request)
    {

        $addressObj = UbicacionGeografica::find($punto);

        if (!$addressObj) {
            return response()->json(['status' => 'error', 'code' => 404], 404);
        }


        $cicloObj = InvCiclo::find($ciclo);

        if (!$cicloObj) {
            return response()->json(['status' => 'error', 'code' => 404], 404);
        }

        if ($cicloObj->puntos()->where('idUbicacionGeo', $punto)->count() === 0) {
            return response()->json(['status' => 'error', 'code' => 404, 'message' => 'La direccion no se corresponde con el ciclo'], 404);
        }


        $queryBuilder = CrudActivo::queryBuilderAsset_Audit_ConfigCycle_FindInAddressGroupFamily_Pagination(
            $cicloObj,
            $punto,
            $codigo,
            $subnivel,
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
    public function showGroupFamilies(int $ciclo, int $punto, string $codigo, int $subnivel, Request $request)
    {

        $this->validateCodigoSubnivel($request, $codigo, $subnivel);

        $addressObj = UbicacionGeografica::find($punto);

        if (!$addressObj) {
            return response()->json(['status' => 'error', 'code' => 404], 404);
        }


        $cicloObj = InvCiclo::find($ciclo);

        if (!$cicloObj) {
            return response()->json(['status' => 'error', 'code' => 404], 404);
        }

        if ($cicloObj->puntos()->where('idUbicacionGeo', $punto)->count() === 0) {
            return response()->json(['status' => 'error', 'code' => 404, 'message' => 'El punto no se corresponde con el ciclo'], 404);
        }

        $emplazamiento = EmplazamientoNn::fromTable('ubicaciones_n' . $subnivel)->where('idAgenda', '=', $punto)->where('codigo', '=', $codigo)->first();

        if (!$emplazamiento) {
            return response()->json(['status' => 'error', 'code' => 404, 'message' => 'El emplazamiento no se corresponde con el punto'], 404);
        }




        $queryBuilder = $emplazamiento->crud_audit_group_families($cicloObj->idCiclo);

        $family_place_resumen = $queryBuilder->get();



        //
        return response()->json([
            'status' => 'OK',
            'data' => GroupFamilyPlaceResumenResource::make($family_place_resumen)
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
}
