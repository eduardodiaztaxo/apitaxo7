<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\EmplazamientoResource;
use App\Http\Resources\V1\GroupFamilyPlaceResumenResource;
use App\Http\Resources\V2\InventariosResource;
use App\Models\Emplazamiento;
use App\Models\EmplazamientoN1;
use App\Models\EmplazamientoN2;
use App\Models\EmplazamientoN3;
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


    /**
     * Display assets of the specified resource.
     *
     * @param   int $ciclo 
     * @param   int $emplazamiento
     * @param   \Illuminate\Http\Request
     * @return  \Illuminate\Http\Response
     */
    public function showAssetsN1(int $ciclo, int $emplazamiento, Request $request)
    {

        $emplaN1Obj = EmplazamientoN1::find($emplazamiento);

        if (!$emplaN1Obj) {
            return response()->json(['status' => 'error', 'code' => 404], 404);
        }


        $cicloObj = InvCiclo::find($ciclo);

        if (!$cicloObj) {
            return response()->json(['status' => 'error', 'code' => 404], 404);
        }

        if ($cicloObj->puntos()->where('idUbicacionGeo', $emplaN1Obj->idAgenda)->count() === 0) {
            return response()->json(['status' => 'NOK', 'code' => 404, 'message' => 'El emplazamiento no se corresponde con el ciclo'], 404);
        }


        $queryBuilder = $queryBuilder = $this->queryBuilderInventory($emplaN1Obj, $cicloObj, $request);

        $assets = $queryBuilder->get();

        //
        return response()->json([
            'status' => 'OK',
            'data' => InventariosResource::collection($assets)
        ]);
    }

    /**
     * Display assets of the specified resource.
     *
     * @param   int $ciclo 
     * @param   int $emplazamiento
     * @param   \Illuminate\Http\Request
     * @return  \Illuminate\Http\Response
     */
    public function showAssetsN2(int $ciclo, int $emplazamiento, Request $request)
    {

        $emplaN2Obj = EmplazamientoN2::find($emplazamiento);

        if (!$emplaN2Obj) {
            return response()->json(['status' => 'error', 'code' => 404], 404);
        }


        $cicloObj = InvCiclo::find($ciclo);

        if (!$cicloObj) {
            return response()->json(['status' => 'NOK', 'code' => 404], 404);
        }

        if ($cicloObj->puntos()->where('idUbicacionGeo', $emplaN2Obj->idAgenda)->count() === 0) {
            return response()->json(['status' => 'error', 'code' => 404, 'message' => 'El emplazamiento no se corresponde con el ciclo'], 404);
        }


        $queryBuilder = $this->queryBuilderInventory($emplaN2Obj, $cicloObj, $request);

        $assets = $queryBuilder->get();

        //
        return response()->json([
            'status' => 'OK',
            'data' => InventariosResource::collection($assets)
        ]);
    }


    /**
     * Display assets of the specified resource.
     *
     * @param   int $ciclo 
     * @param   int $emplazamiento
     * @param   \Illuminate\Http\Request
     * @return  \Illuminate\Http\Response
     */
    public function showAssetsN3(int $ciclo, int $emplazamiento, Request $request)
    {

        $emplaN2Obj = EmplazamientoN3::find($emplazamiento);

        if (!$emplaN2Obj) {
            return response()->json(['status' => 'error', 'code' => 404], 404);
        }


        $cicloObj = InvCiclo::find($ciclo);

        if (!$cicloObj) {
            return response()->json(['status' => 'NOK', 'code' => 404], 404);
        }

        if ($cicloObj->puntos()->where('idUbicacionGeo', $emplaN2Obj->idAgenda)->count() === 0) {
            return response()->json(['status' => 'error', 'code' => 404, 'message' => 'El emplazamiento no se corresponde con el ciclo'], 404);
        }


        $queryBuilder = $this->queryBuilderInventory($emplaN2Obj, $cicloObj, $request);

        $assets = $queryBuilder->get();

        //
        return response()->json([
            'status' => 'OK',
            'data' => InventariosResource::collection($assets)
        ]);
    }


    public function queryBuilderInventory($model, InvCiclo $cicloObj, Request $request)
    {
        $queryBuilder = $model->inv_activos()->where('inv_inventario.id_ciclo', $cicloObj->idCiclo);

        if (!!keyword_is_searcheable($request->keyword)) {
            $complete_word = trim($request->keyword);
            $possible_name_words = keyword_search_terms_from_keyword($request->keyword);

            $queryBuilder = $queryBuilder->join('dp_familias', 'inv_inventario.id_familia', 'dp_familias.id_familia');

            $queryBuilder = $queryBuilder
                ->where(function ($query) use ($complete_word) {
                    $query->where('inv_inventario.descripcion_bien', 'LIKE', "%$complete_word%");
                    $query->orWhere('inv_inventario.etiqueta', 'LIKE', "%$complete_word%");
                    $query->orWhere('dp_familias.descripcion_familia', 'LIKE', "%$complete_word%");
                });

            if (count($possible_name_words) > 1) {
                $queryBuilder = $queryBuilder->orWhere(function ($query) use ($possible_name_words) {
                    foreach ($possible_name_words as $palabra) {
                        $query->where('inv_inventario.descripcion_bien', 'LIKE', "%$palabra%");
                    }
                });

                $queryBuilder = $queryBuilder->orWhere(function ($query) use ($possible_name_words) {
                    foreach ($possible_name_words as $palabra) {
                        $query->where('dp_familias.descripcion_familia', 'LIKE', "%$palabra%");
                    }
                });
            }
        }

        if ($request->from && $request->rows) {
            $offset = $request->from - 1;
            $limit = $request->rows;
            $queryBuilder->offset($offset)->limit($limit);
        }

        return $queryBuilder;
    }

    /**
     * Display families of the specified resource.
     *
     * @param   int $ciclo 
     * @param   int $emplazamiento
     * @param   \Illuminate\Http\Request
     * @return  \Illuminate\Http\Response
     */
    public function showGroupFamilies(int $ciclo, int $emplazamiento, Request $request)
    {

        $emplaN2Obj = EmplazamientoN2::find($emplazamiento);

        if (!$emplaN2Obj) {
            return response()->json(['status' => 'error', 'code' => 404], 404);
        }


        $cicloObj = InvCiclo::find($ciclo);

        if (!$cicloObj) {
            return response()->json(['status' => 'error', 'code' => 404], 404);
        }

        if ($cicloObj->puntos()->where('idUbicacionGeo', $emplaN2Obj->idAgenda)->count() === 0) {
            return response()->json(['status' => 'error', 'code' => 404, 'message' => 'El emplazamiento no se corresponde con el ciclo'], 404);
        }


        $queryBuilder = $emplaN2Obj->inv_group_families()->where('inv_inventario.id_ciclo', $cicloObj->idCiclo);


        $family_place_resumen = $queryBuilder->get();



        //
        return response()->json([
            'status' => 'OK',
            'data' => GroupFamilyPlaceResumenResource::make($family_place_resumen)
        ]);
    }


    /**
     * Display families of the specified resource.
     *
     * @param   int $ciclo 
     * @param   int $emplazamiento
     * @param   \Illuminate\Http\Request
     * @return  \Illuminate\Http\Response
     */
    public function showGroupFamiliesN1(int $ciclo, int $emplazamiento, Request $request)
    {

        $emplaN2Obj = EmplazamientoN1::find($emplazamiento);

        if (!$emplaN2Obj) {
            return response()->json(['status' => 'error', 'code' => 404], 404);
        }


        $cicloObj = InvCiclo::find($ciclo);

        if (!$cicloObj) {
            return response()->json(['status' => 'error', 'code' => 404], 404);
        }

        if ($cicloObj->puntos()->where('idUbicacionGeo', $emplaN2Obj->idAgenda)->count() === 0) {
            return response()->json(['status' => 'error', 'code' => 404, 'message' => 'El emplazamiento no se corresponde con el ciclo'], 404);
        }


        $queryBuilder = $emplaN2Obj->inv_group_families()->where('inv_inventario.id_ciclo', $cicloObj->idCiclo);


        $family_place_resumen = $queryBuilder->get();



        //
        return response()->json([
            'status' => 'OK',
            'data' => GroupFamilyPlaceResumenResource::make($family_place_resumen)
        ]);
    }


    /**
     * Display families of the specified resource.
     *
     * @param   int $ciclo 
     * @param   int $emplazamiento
     * @param   \Illuminate\Http\Request
     * @return  \Illuminate\Http\Response
     */
    public function showGroupFamiliesN2(int $ciclo, int $emplazamiento, Request $request)
    {

        $emplaN2Obj = EmplazamientoN2::find($emplazamiento);

        if (!$emplaN2Obj) {
            return response()->json(['status' => 'error', 'code' => 404], 404);
        }


        $cicloObj = InvCiclo::find($ciclo);

        if (!$cicloObj) {
            return response()->json(['status' => 'error', 'code' => 404], 404);
        }

        if ($cicloObj->puntos()->where('idUbicacionGeo', $emplaN2Obj->idAgenda)->count() === 0) {
            return response()->json(['status' => 'error', 'code' => 404, 'message' => 'El emplazamiento no se corresponde con el ciclo'], 404);
        }


        $queryBuilder = $emplaN2Obj->inv_group_families()->where('inv_inventario.id_ciclo', $cicloObj->idCiclo);


        $family_place_resumen = $queryBuilder->get();



        //
        return response()->json([
            'status' => 'OK',
            'data' => GroupFamilyPlaceResumenResource::make($family_place_resumen)
        ]);
    }

    /**
     * Display families of the specified resource.
     *
     * @param   int $ciclo 
     * @param   int $emplazamiento
     * @param   \Illuminate\Http\Request
     * @return  \Illuminate\Http\Response
     */
    public function showGroupFamiliesN3(int $ciclo, int $emplazamiento, Request $request)
    {

        $emplaN2Obj = EmplazamientoN3::find($emplazamiento);

        if (!$emplaN2Obj) {
            return response()->json(['status' => 'error', 'code' => 404], 404);
        }


        $cicloObj = InvCiclo::find($ciclo);

        if (!$cicloObj) {
            return response()->json(['status' => 'error', 'code' => 404], 404);
        }

        if ($cicloObj->puntos()->where('idUbicacionGeo', $emplaN2Obj->idAgenda)->count() === 0) {
            return response()->json(['status' => 'error', 'code' => 404, 'message' => 'El emplazamiento no se corresponde con el ciclo'], 404);
        }


        $queryBuilder = $emplaN2Obj->inv_group_families()->where('inv_inventario.id_ciclo', $cicloObj->idCiclo);


        $family_place_resumen = $queryBuilder->get();



        //
        return response()->json([
            'status' => 'OK',
            'data' => GroupFamilyPlaceResumenResource::make($family_place_resumen)
        ]);
    }
}
