<?php

namespace App\Http\Controllers\Api\V2;

use App\Http\Controllers\Controller;
use App\Http\Resources\V2\EmplazamientoNivel1Resource;
use App\Models\EmplazamientoN1;
use Illuminate\Http\Request;

class EmplazamientoNivel1Controller extends Controller
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
     * @param  int  $emplazamiento_id
     * @return \Illuminate\Http\Response
     */
    public function show(int $ciclo, int $emplazamiento)
    {

        $emplaObj = EmplazamientoN1::find($emplazamiento);



        if (!$emplaObj) {
            return response()->json(['status' => 'error', 'code' => 404], 404);
        }


        $emplaObj->requirePunto = 1;

        $emplaObj->cycle_id = $ciclo;

        $resource = EmplazamientoNivel1Resource::make($emplaObj);



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
