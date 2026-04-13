<?php

namespace App\Http\Controllers\Api\V2\Auditoria;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\CrudActivoLiteResource;
use App\Http\Resources\V1\GroupFamilyPlaceResumenResource;
use App\Http\Resources\V2\Auditoria\UbicacionGeograficaAuditoriaResource;
use App\Models\CrudActivo;
use App\Models\InvCiclo;
use App\Models\UbicacionGeografica;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CiclosAuditoriaUbicacionesController extends Controller
{

    /**
     * Display the specified resource.
     *
     * @param \Illuminate\Http\Request $request
     * @param  int  $ciclo
     * @param  int  $punto
     * @return \Illuminate\Http\Response
     */
    public function showOne(Request $request, int $ciclo, int $punto)
    {

        $puntoObj = UbicacionGeografica::find($punto);

        if (!$puntoObj) {
            return response()->json(['status' => 'NOK', 'code' => 404], 404);
        }


        $cycleObj = InvCiclo::find($ciclo);

        if (!$cycleObj) {
            return response()->json(['status' => 'NOK', 'code' => 404], 404);
        }

        if ($cycleObj->puntos()->where('idUbicacionGeo', $puntoObj->idUbicacionGeo)->count() === 0) {
            return response()->json(['status' => 'NOK', 'code' => 404, 'message' => 'La dirección no se corresponde con el ciclo'], 404);
        }

        $resource = new UbicacionGeograficaAuditoriaResource($puntoObj, $cycleObj);




        //
        return response()->json(['status' => 'OK', 'data' => $resource], 200);
    }




    /**
     * Display the specified resource.
     *
     * @param \Illuminate\Http\Request $request
     * @param  int  $ciclo
     * @return \Illuminate\Http\Response
     */
    public function showByCycleAndGrupFamily(Request $request, int $ciclo)
    {
        //return $request->user()->conn_field;
        //
        $cycleObj = InvCiclo::find($ciclo);

        if (!$cycleObj) {
            return response()->json(['status' => 'NOK', 'code' => 404], 404);
        }

        $user = $request->user();

        $usuario = $user?->name;
        $puntos = $cycleObj->ciclo_puntos_users($usuario, $ciclo, $request->keyword, $request->from, $request->rows)->get();



        if ($puntos->isEmpty()) {
            $puntos = $cycleObj->puntos($request->keyword, $request->from, $request->rows)->get();
        }


        $resources = $puntos->map(function ($punto) use ($cycleObj) {
            return new UbicacionGeograficaAuditoriaResource($punto, $cycleObj);
        });

        return response()->json(['status' => 'OK', 'data' => $resources], 200);
    }



    /**
     * Display assets of the specified resource.
     *
     * @param   int $ciclo 
     * @param   int $punto
     * @param   \Illuminate\Http\Request
     * @return  \Illuminate\Http\Response
     */
    public function showAssetsByUbicacion(int $ciclo, int $punto, Request $request)
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
    public function showGroupFamilies(int $ciclo, int $punto, Request $request)
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
            return response()->json(['status' => 'error', 'code' => 404, 'message' => 'El punto no se corresponde con el ciclo'], 404);
        }


        $queryBuilder = $addressObj->crud_audit_group_families($cicloObj->idCiclo);

        $family_place_resumen = $queryBuilder->get();



        //
        return response()->json([
            'status' => 'OK',
            'data' => GroupFamilyPlaceResumenResource::make($family_place_resumen)
        ]);
    }


    /**
     * Display emplazamientos (sub-levels) for a given point and cycle filtered by codigo/subnivel.
     *
     * @param  int $ciclo
     * @param  int $punto
     * @param  string $codigo
     * @param  int $subnivel
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function showEmplazamientosByCycleAndGrupFamily(int $ciclo, int $punto, $codigo, int $subnivel, Request $request)
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
            return response()->json(['status' => 'error', 'code' => 404, 'message' => 'El punto no se corresponde con el ciclo'], 404);
        }

        switch ($subnivel) {
            case 1:
                $model = \App\Models\EmplazamientoN1::class;
                $resource = \App\Http\Resources\V2\EmplazamientoNivel1Resource::class;
                break;
            case 2:
                $model = \App\Models\EmplazamientoN2::class;
                $resource = \App\Http\Resources\V2\EmplazamientoNivel2LiteResource::class;
                break;
            case 3:
                $model = \App\Models\EmplazamientoN3::class;
                $resource = \App\Http\Resources\V2\EmplazamientoNivel3LiteResource::class;
                break;
            default:
                return response()->json(['status' => 'error', 'message' => 'Nivel no válido'], 400);
        }

        $query = $model::where('idAgenda', $addressObj->idUbicacionGeo)
            ->where('codigoUbicacion', 'LIKE', $codigo . '%');

        if ($request->filled('keyword')) {
            $kw = $request->keyword;
            $query->where('descripcionUbicacion', 'LIKE', "%{$kw}%");
        }

        if ($request->filled('from') && $request->filled('rows')) {
            $offset = max(0, (int)$request->from - 1);
            $limit = (int)$request->rows;
            $query->offset($offset)->limit($limit);
        }

        $emplazamientos = $query->get();

        foreach ($emplazamientos as $e) {
            $e->cycle_id = $ciclo;
        }

        return response()->json([
            'status' => 'OK',
            'data' => $resource::collection($emplazamientos)
        ], 200);
    }
}
