<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\UbicacionGeograficaResource;
use App\Models\InvCiclo;
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


        return response()->json(UbicacionGeograficaResource::collection($puntos), 200);
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

        /*** Filtrar Zonas que contengan categorías a inventariar ***/
        /*
        * Obtener Categorias del Ciclo
        * Obtener puntos del Ciclo
        * Obtener Zonas que contengan las categorias para los puntos
        */

        $sql = "SELECT 
        crud_activos.ubicacionGeografica AS punto,
        crud_activos.ubicacionOrganicaN1 AS zona
        FROM
        inv_ciclos
        INNER JOIN inv_ciclos_puntos ON inv_ciclos.idCiclo = inv_ciclos_puntos.idCiclo
        INNER JOIN inv_ciclos_categorias ON inv_ciclos.idCiclo = inv_ciclos_categorias.idCiclo
        INNER JOIN crud_activos 
            ON inv_ciclos_puntos.idPunto =  crud_activos.ubicacionGeografica 
                AND inv_ciclos_categorias.categoria1 = crud_activos.categoriaN1
                AND inv_ciclos_categorias.categoria2 = crud_activos.categoriaN2
                AND inv_ciclos_categorias.categoria3 = crud_activos.categoriaN3
        WHERE inv_ciclos.idCiclo = ?
        GROUP BY crud_activos.ubicacionGeografica, crud_activos.ubicacionOrganicaN1 ";


        $zonasContsCats = DB::select($sql, [$ciclo]);

        $zonas = collect($zonasContsCats)->pluck('zona')->toArray();

        foreach ($puntos as $punto) {
            $punto->zonas_cats = $zonas;
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
