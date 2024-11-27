<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\EmplazamientoResource;
use App\Models\Emplazamiento;
use App\Models\InvCiclo;
use Illuminate\Http\Request;

class CiclosEmplazamientosController extends Controller
{
    /**
     * Display the specified resource.
     *
     * @param  int  $emplazamiento_id
     * @return \Illuminate\Http\Response
     */
    public function show(int $ciclo, int $emplazamiento)
    {

        $emplaObj = Emplazamiento::find($emplazamiento);

        if (!$emplaObj) {
            return response()->json(['status' => 'NOK', 'code' => 404], 404);
        }


        $cicloObj = InvCiclo::find($ciclo);

        if (!$cicloObj) {
            return response()->json(['status' => 'NOK', 'code' => 404], 404);
        }

        if ($cicloObj->puntos()->where('idUbicacionGeo', $emplaObj->idAgenda)->count() === 0) {
            return response()->json(['status' => 'NOK', 'code' => 404, 'message' => 'El emplazamiento no se corresponde con el ciclo'], 404);
        }


        $emplaObj->requirePunto = 1;
        $emplaObj->requireActivos = 1;
        $emplaObj->cycle_id = $ciclo;

        //
        return response()->json(EmplazamientoResource::make($emplaObj));
    }
}
