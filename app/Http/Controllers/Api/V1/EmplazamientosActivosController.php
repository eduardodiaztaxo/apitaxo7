<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\CrudActivoResource;
use App\Models\Emplazamiento;
use App\Models\InvCiclo;
use App\Services\ActivoService;
use Illuminate\Http\Request;

class EmplazamientosActivosController extends Controller
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
    public function show($emplazamiento)
    {
        $empObj = Emplazamiento::find($emplazamiento);

        if (!$empObj) {
            return response()->json(['status' => 'NOK', 'code' => 404], 404);
        }

        $activos = $empObj->activos()->get();



        return response()->json(CrudActivoResource::collection($activos), 200);
    }



    public function showOnlyLabels($emplazamiento)
    {
        $empObj = Emplazamiento::find($emplazamiento);

        if (!$empObj) {
            return response()->json(['status' => 'NOK', 'code' => 404], 404);
        }

        $etiquetas = $empObj->activos()->get()->pluck('etiqueta');



        return response()->json($etiquetas, 200);
    }


    /**
     * Show labels by cycle and categories.
     *
     * @param  int  $ciclo
     * @param  int  $emplazamiento 
     * @return \Illuminate\Http\Response
     */
    public function showOnlyLabelsByCycleCats(int $ciclo, int $emplazamiento)
    {
        $empObj = Emplazamiento::find($emplazamiento);

        if (!$empObj) {
            return response()->json(['status' => 'NOK', 'code' => 404], 404);
        }

        $cicloObj = InvCiclo::find($ciclo);

        if (!$cicloObj) {
            return response()->json(['status' => 'NOK', 'message' => 'Ciclo no encontrado', 'code' => 404], 404);
        }

        $etiquetas = ActivoService::getLabelsByCycleAndEmplazamiento($empObj, $cicloObj);



        return response()->json($etiquetas, 200);
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
