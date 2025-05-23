<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\UbicacionGeograficaResource;
use App\Models\InvCiclo;
use App\Models\InvCicloPunto;
use App\Models\UbicacionGeografica;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CiclosUbicacionesController extends Controller
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
    public function show(Request $request, int $ciclo)
    {
        //return $request->user()->conn_field;
        //
        $cicloObj = InvCiclo::find($ciclo);

        if (!$cicloObj) {
            return response()->json(['status' => 'NOK', 'code' => 404], 404);
        }

        $puntos = $cicloObj->puntos()->get();

        foreach ($puntos as $punto) {
            $punto->requireZonas = 1;
        }


        return response()->json(UbicacionGeograficaResource::collection($puntos), 200);
    }

    /**
     * Display address resource.
     *
     * @param  int  $ciclo 
     * * @param  int  $punto
     * @return \Illuminate\Http\Response
     */
    public function showAll(Request $request, int $ciclo, int $punto)
    {

        $puntoObj = UbicacionGeografica::find($punto);

        if (!$puntoObj) {
            return response()->json(['status' => 'NOK', 'code' => 404], 404);
        }


        $cicloObj = InvCiclo::find($ciclo);

        if (!$cicloObj) {
            return response()->json(['status' => 'NOK', 'code' => 404], 404);
        }

        if ($cicloObj->puntos()->where('idUbicacionGeo', $puntoObj->idUbicacionGeo)->count() === 0) {
            return response()->json(['status' => 'NOK', 'code' => 404, 'message' => 'La dirección no se corresponde con el ciclo'], 404);
        }



        $puntoObj->requireActivos = 1;
        $puntoObj->cycle_id = $cicloObj->idCiclo;


        //
        return response()->json(UbicacionGeograficaResource::make($puntoObj));
    }






    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function showByCycleCats(Request $request, int $ciclo)
    {
        //return $request->user()->conn_field;
        //
        $cicloObj = InvCiclo::find($ciclo);

        if (!$cicloObj) {
            return response()->json(['status' => 'NOK', 'code' => 404], 404);
        }

        $puntos = $cicloObj->puntos()->get();


        //$zonas = $cicloObj->zonesWithCats()->pluck('zona')->toArray();
        //¿La zona tiene bienes que no están asociados a emplazamientos?

        foreach ($puntos as $punto) {
            //$punto->zonas_cats = $zonas;
            $punto->requireZonas = 1;
            $punto->cycle_id = $ciclo;
            //Si el ciclo es auditoría y la auditoría es general, el atributo auditoria_general se pone a 1
            if ($cicloObj->idTipoCiclo == 2) {

                $InvCicloPunto = InvCicloPunto::where('idCiclo', $ciclo)->where('idPunto', $punto->idUbicacionGeo)->first();

                if ($InvCicloPunto) {
                    $punto->auditoria_general = $InvCicloPunto->auditoria_general;
                } else {
                    $punto->auditoria_general = 0;
                }
            }
        }



        return response()->json(UbicacionGeograficaResource::collection($puntos), 200);
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
