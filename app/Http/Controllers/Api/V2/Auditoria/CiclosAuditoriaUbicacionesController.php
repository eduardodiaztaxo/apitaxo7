<?php

namespace App\Http\Controllers\Api\V2\Auditoria;

use App\Http\Controllers\Controller;
use App\Http\Resources\V2\Auditoria\UbicacionGeograficaAuditoriaResource;
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
}
