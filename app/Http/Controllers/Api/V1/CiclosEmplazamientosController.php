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
use App\Models\Inventario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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

        $cicloObj = null;
        if ($ciclo != 0) {
            $cicloObj = InvCiclo::find($ciclo);

            if (!$cicloObj) {
                return response()->json(['status' => 'error', 'code' => 404, 'message' => 'Ciclo no encontrado'], 404);
            }

            if ($cicloObj->puntos()->where('idUbicacionGeo', $emplaN1Obj->idAgenda)->count() === 0) {
                return response()->json(['status' => 'NOK', 'code' => 404, 'message' => 'El emplazamiento no se corresponde con el ciclo'], 404);
            }
        }

        $queryBuilder = Inventario::queryBuilderInventory_FindInGroupFamily_Pagination($emplaN1Obj, $cicloObj, $request);

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

        $cicloObj = null;
        if ($ciclo != 0) {
            $cicloObj = InvCiclo::find($ciclo);

            if (!$cicloObj) {
                return response()->json(['status' => 'NOK', 'code' => 404, 'message' => 'Ciclo no encontrado'], 404);
            }

            if ($cicloObj->puntos()->where('idUbicacionGeo', $emplaN2Obj->idAgenda)->count() === 0) {
                return response()->json(['status' => 'error', 'code' => 404, 'message' => 'El emplazamiento no se corresponde con el ciclo'], 404);
            }
        }

        $queryBuilder = Inventario::queryBuilderInventory_FindInGroupFamily_Pagination($emplaN2Obj, $cicloObj, $request);

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

        $emplaN3Obj = EmplazamientoN3::find($emplazamiento);

        if (!$emplaN3Obj) {
            return response()->json(['status' => 'error', 'code' => 404], 404);
        }

        $cicloObj = null;
        if ($ciclo != 0) {
            $cicloObj = InvCiclo::find($ciclo);

            if (!$cicloObj) {
                return response()->json(['status' => 'NOK', 'code' => 404, 'message' => 'Ciclo no encontrado'], 404);
            }

            if ($cicloObj->puntos()->where('idUbicacionGeo', $emplaN3Obj->idAgenda)->count() === 0) {
                return response()->json(['status' => 'error', 'code' => 404, 'message' => 'El emplazamiento nievl  3 no se corresponde con el ciclo'], 404);
            }
        }

        $queryBuilder = Inventario::queryBuilderInventory_FindInGroupFamily_Pagination($emplaN3Obj, $cicloObj, $request);

        $assets = $queryBuilder->get();

        //
        return response()->json([
            'status' => 'OK',
            'data' => InventariosResource::collection($assets)
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
    public function showGroupFamilies(int $ciclo, int $emplazamiento, Request $request)
    {

        $emplaN2Obj = EmplazamientoN2::find($emplazamiento);

        if (!$emplaN2Obj) {
            return response()->json(['status' => 'error', 'code' => 404], 404);
        }

        $cicloObj = null;
        if ($ciclo != 0) {
            $cicloObj = InvCiclo::find($ciclo);

            if (!$cicloObj) {
                return response()->json(['status' => 'error', 'code' => 404, 'message' => 'Ciclo no encontrado'], 404);
            }

            if ($cicloObj->puntos()->where('idUbicacionGeo', $emplaN2Obj->idAgenda)->count() === 0) {
                return response()->json(['status' => 'error', 'code' => 404, 'message' => 'El emplazamiento no se corresponde con el ciclo'], 404);
            }
        }

        $queryBuilder = $emplaN2Obj->inv_group_families();
        if ($cicloObj) {
            $queryBuilder->where('inv_inventario.id_ciclo', $cicloObj->idCiclo);
        }

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

        $emplaObj = EmplazamientoN1::find($emplazamiento);

        if (!$emplaObj) {
            return response()->json(['status' => 'error', 'code' => 404], 404);
        }

        $cicloObj = null;
        if ($ciclo != 0) {
            $cicloObj = InvCiclo::find($ciclo);

            if (!$cicloObj) {
                return response()->json(['status' => 'error', 'code' => 404, 'message' => 'Ciclo no encontrado'], 404);
            }

            if ($cicloObj->puntos()->where('idUbicacionGeo', $emplaObj->idAgenda)->count() === 0) {
                return response()->json(['status' => 'error', 'code' => 404, 'message' => 'El emplazamiento no se corresponde con el ciclo'], 404);
            }
        }

        $queryBuilder = $emplaObj->inv_group_families();
        if ($cicloObj) {
            $queryBuilder->where('inv_inventario.id_ciclo', $cicloObj->idCiclo);
        }

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

        $emplaObj = EmplazamientoN2::find($emplazamiento);

        if (!$emplaObj) {
            return response()->json(['status' => 'error', 'code' => 404], 404);
        }

        $cicloObj = null;
        if ($ciclo != 0) {
            $cicloObj = InvCiclo::find($ciclo);

            if (!$cicloObj) {
                return response()->json(['status' => 'error', 'code' => 404], 404);
            }

            if ($cicloObj->puntos()->where('idUbicacionGeo', $emplaObj->idAgenda)->count() === 0) {
                return response()->json(['status' => 'error', 'code' => 404, 'message' => 'El emplazamiento no se corresponde con el ciclo'], 404);
            }
        }

        $queryBuilder = $emplaObj->inv_group_families();
        if ($cicloObj) {
            $queryBuilder->where('inv_inventario.id_ciclo', $cicloObj->idCiclo);
        }

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

        $emplaObj = EmplazamientoN3::find($emplazamiento);

        if (!$emplaObj) {
            return response()->json(['status' => 'error', 'code' => 404], 404);
        }

        $cicloObj = null;
        if ($ciclo != 0) {
            $cicloObj = InvCiclo::find($ciclo);

            if (!$cicloObj) {
                return response()->json(['status' => 'error', 'code' => 404], 404);
            }

            if ($cicloObj->puntos()->where('idUbicacionGeo', $emplaObj->idAgenda)->count() === 0) {
                return response()->json(['status' => 'error', 'code' => 404, 'message' => 'El emplazamiento no se corresponde con el ciclo'], 404);
            }
        }

        $queryBuilder = $emplaObj->inv_group_families();
        if ($cicloObj) {
            $queryBuilder->where('inv_inventario.id_ciclo', $cicloObj->idCiclo);
        }

        $family_place_resumen = $queryBuilder->get();

        //
        return response()->json([
            'status' => 'OK',
            'data' => GroupFamilyPlaceResumenResource::make($family_place_resumen)
        ]);
    }

    /**
     * Display assets of the specified resource.
     *
     * @param   int $ciclo 
     * @param   int $nivel
     * @param   int $emplazamiento
     * @param   \Illuminate\Http\Request
     * @return  \Illuminate\Http\Response
     */
    public function showAssetsByLevel(int $ciclo, int $nivel, int $emplazamiento, Request $request)
    {
        switch ($nivel) {
            case 1:
                return $this->showAssetsN1($ciclo, $emplazamiento, $request);
            case 2:
                return $this->showAssetsN2($ciclo, $emplazamiento, $request);
            case 3:
                return $this->showAssetsN3($ciclo, $emplazamiento, $request);
            default:
                return response()->json(['status' => 'error', 'message' => 'Nivel no válido'], 400);
        }
    }

    /**
     * Display assets of the specified resource by level.
     *
     * @param   int $ciclo 
     * @param   int $nivel
     * @param   int $emplazamiento
     * @param   \Illuminate\Http\Request
     * @return  \Illuminate\Http\Response
     */
    public function showAssetsByLevel_New(int $ciclo, int $nivel, int $emplazamiento, Request $request)
    {
        $tableName = 'ubicaciones_n' . $nivel;
        $idFieldName = 'idUbicacionN' . $nivel;

        $emplaObj = DB::table($tableName)
            ->where($idFieldName, $emplazamiento)
            ->first();

        if (!$emplaObj) {
            return response()->json(['status' => 'error', 'code' => 404], 404);
        }

        $emplaObj = (object) $emplaObj;

        $cicloObj = null;
        if ($ciclo != 0) {
            $cicloObj = InvCiclo::find($ciclo);

            if (!$cicloObj) {
                return response()->json(['status' => 'error', 'code' => 404], 404);
            }

            if ($cicloObj->puntos()->where('idUbicacionGeo', $emplaObj->idAgenda)->count() === 0) {
                return response()->json(['status' => 'NOK', 'code' => 404, 'message' => 'El emplazamiento no se corresponde con el ciclo'], 404);
            }
        }

        $queryBuilder = Inventario::queryBuilderInventory_FindInGroupFamily_Pagination($emplaObj, $cicloObj, $request);

        $assets = $queryBuilder->get();

        return response()->json([
            'status' => 'OK',
            'data' => InventariosResource::collection($assets)
        ]);
    }

    /**
     * Display families of the specified resource by level.
     *
     * @param   int $ciclo 
     * @param   int $nivel
     * @param   int $emplazamiento
     * @param   \Illuminate\Http\Request
     * @return  \Illuminate\Http\Response
     */
    public function showGroupFamiliesByLevel(int $ciclo, int $nivel, int $emplazamiento, Request $request)
    {
        $tableName = 'ubicaciones_n' . $nivel;
        $idFieldName = 'idUbicacionN' . $nivel;

        $emplaObj = DB::table($tableName)
            ->where($idFieldName, $emplazamiento)
            ->first();

        if (!$emplaObj) {
            return response()->json(['status' => 'error', 'code' => 404], 404);
        }

        $emplaObj = (object) $emplaObj;

        $cicloObj = null;
        if ($ciclo != 0) {
            $cicloObj = InvCiclo::find($ciclo);

            if (!$cicloObj) {
                return response()->json(['status' => 'error', 'code' => 404], 404);
            }

            if ($cicloObj->puntos()->where('idUbicacionGeo', $emplaObj->idAgenda)->count() === 0) {
                return response()->json(['status' => 'error', 'code' => 404, 'message' => 'El emplazamiento no se corresponde con el ciclo'], 404);
            }
        }

        $queryBuilder = $emplaObj->inv_group_families();
        if ($cicloObj) {
            $queryBuilder->where('inv_inventario.id_ciclo', $cicloObj->idCiclo);
        }

        $family_place_resumen = $queryBuilder->get();

        return response()->json([
            'status' => 'OK',
            'data' => GroupFamilyPlaceResumenResource::make($family_place_resumen)
        ]);
    }
}
