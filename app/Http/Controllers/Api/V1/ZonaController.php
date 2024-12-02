<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\ZonaPuntoResource;
use App\Models\InvCiclo;
use App\Models\ZonaPunto;
use Illuminate\Http\Request;

class ZonaController extends Controller
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
     * Display zone resource
     *
     * @param  int  $zona
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request, int $zona)
    {
        $zonaObj = ZonaPunto::find($zona);

        if (!$zonaObj) {
            return response()->json(['status' => 'NOK', 'message' => 'Zona no encontrada', 'code' => 404], 404);
        }



        $zonaObj->requireOrphanAssets = 1;
        $zonaObj->requireAddress = 1;

        return response()->json(ZonaPuntoResource::make($zonaObj), 200);
    }


    /**
     * Display zone resource by cycle constraints.
     *
     * @param  int  $ciclo
     * @param  int  $zona
     * @return \Illuminate\Http\Response
     */
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



        $count = $cicloObj->zonesWithCats()->where('punto', $zonaObj->idAgenda)->where('zona', $zonaObj->codigoUbicacion)->count();

        if (!$count || $count === 0) {
            return response()->json(['status' => 'NOK', 'message' => 'Zona no asociada al Ciclo', 'code' => 404], 404);
        }

        $zonaObj->cycle_id = $ciclo;
        $zonaObj->requireOrphanAssets = 1;
        $zonaObj->requireAddress = 1;

        return response()->json(ZonaPuntoResource::make($zonaObj), 200);
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
