<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\EmplazamientoResource;
use App\Http\Resources\V1\EmplazamientoNivel3Resource;
use App\Models\InvCiclo;
use App\Models\ZonaPunto;
use App\Models\EmplazamientoN3;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ZonaEmplazamientosController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request, int $zona)
    {
        $zonaObj = ZonaPunto::find($zona);

        if (!$zonaObj) {
            return response()->json(['status' => 'NOK', 'code' => 404], 404);
        }

        $emplazamientos = $zonaObj->emplazamientos()->get();

        return response()->json([
            'message' => $request->get('middleware_message'),
            'data' => EmplazamientoResource::collection($emplazamientos)
        ], 200);
    }


    public function showByCycleCats(Request $request, int $ciclo, int $zona)
    {
        //return $request->user()->conn_field;
        //
        $zonaObj = ZonaPunto::find($zona);

        if (!$zonaObj) {
            return response()->json(['status' => 'NOK', 'message' => 'Zona no encontrada', 'code' => 404], 404);
        }


        $cicloObj = InvCiclo::find($ciclo);

        if (!$cicloObj) {
            return response()->json(['status' => 'NOK', 'message' => 'Ciclo no encontrado', 'code' => 404], 404);
        }



        $emplaCats = $cicloObj->zoneEmplazamientosWithCats($zonaObj)->pluck('idUbicacionN2')->toArray();



        if (empty($emplaCats)) {
            $emplazamientos = $zonaObj->emplazamientos()->get();
        } else {
            $emplazamientos = $zonaObj->emplazamientos()->whereIn('idUbicacionN2', $emplaCats)->get();
        }

        foreach ($emplazamientos as $emplazamiento) {
            $emplazamiento->cycle_id = $ciclo;
        }


        return response()->json(EmplazamientoResource::collection($emplazamientos), 200);
    }


 public function CycleCatsNivel3(Request $request, int $ciclo, int $zona, int $id)
    {
    $zonaObj = EmplazamientoN3::find($id);

        if (!$zonaObj) {
            return response()->json(['status' => 'NOK', 'message' => 'Zona no encontrada', 'code' => 404], 404);
        }


        $cicloObj = InvCiclo::find($ciclo);

        if (!$cicloObj) {
            return response()->json(['status' => 'NOK', 'message' => 'Ciclo no encontrado', 'code' => 404], 404);
        }


       $emplaCats = $cicloObj->zoneSubEmplazamientosWithCats($zonaObj)->pluck('idUbicacionN3')->toArray();


        if (empty($emplaCats)) {
            $emplazamientos = $zonaObj->subemplazamientosNivel3()->get();
        } else {
            $emplazamientos = $zonaObj->subemplazamientosNivel3()->whereIn('idUbicacionN3', $emplaCats)->get();
        }

        foreach ($emplazamientos as $emplazamiento) {
            $emplazamiento->cycle_id = $ciclo;
        }


        return response()->json(EmplazamientoNivel3Resource::collection($emplazamientos), 200);
    }
    public function showAllEmplaByCycleCats(Request $request, int $ciclo)
    {

        $cicloObj = InvCiclo::find($ciclo);

        if (!$cicloObj) {
            return response()->json(['status' => 'NOK', 'message' => 'Ciclo no encontrado', 'code' => 404], 404);
        }


        if ($cicloObj->idTipoCiclo == 1) {
            $emplazamientos = $cicloObj->emplazamientos_with_cats_inv()->get();
        } else {
            $emplazamientos = $cicloObj->emplazamientos_with_cats()->get();
        }

        foreach ($emplazamientos as $emplazamiento) {
            $emplazamiento->cycle_id = $ciclo;
        }

        return response()->json(EmplazamientoResource::collection($emplazamientos), 200);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
