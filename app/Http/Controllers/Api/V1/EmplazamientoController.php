<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\EmplazamientoResource;
use App\Models\CrudActivo;
use App\Models\Emplazamiento;
use Illuminate\Http\Request;

class EmplazamientoController extends Controller
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
     * check if exists emplazamiento.
     *
     * @param  int  $punto
     * @param  int  $emplazamiento_code 
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function existsEmplazamiento(int $punto, int $emplazamiento_code, Request $request)
    {

        $query = Emplazamiento::where('idAgenda', $punto)->where('codigoUbicacion', $emplazamiento_code);

        $q = $query->get()->count();

        //
        return response()->json(['status' => 'OK', 'data' => [
            'exists' => $q > 0,
            'emplazamiento' => EmplazamientoResource::make($query->first())
        ]]);
    }


    /**
     * Display the specified resource.
     *
     * @param  int  $emplazamiento_id
     * @return \Illuminate\Http\Response
     */
    public function show(int $emplazamiento)
    {

        $emplaObj = Emplazamiento::find($emplazamiento);

        if (!$emplaObj) {
            return response()->json(['status' => 'NOK', 'code' => 404], 404);
        }

        $emplaObj->requirePunto = 1;

        $emplaObj->requireActivos = 1;

        $resource = EmplazamientoResource::make($emplaObj);



        //$resource->activos = $activos;
        //
        return response()->json($resource);
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
