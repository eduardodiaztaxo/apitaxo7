<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\GroupFamilyPlaceResumenResource;
use App\Http\Resources\V1\InventariosResource;
use App\Http\Resources\V1\UbicacionGeograficaResource;
use App\Models\InvCiclo;
use App\Models\InvCicloPunto;
use App\Models\Inventario;
use App\Models\UbicacionGeografica;
use App\Services\ProyectoUsuarioService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

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
        $request->validate([
            'descripcion'       => 'required|string',
            'direccion'         => 'required|string',
            'ciclo_auditoria'   => 'required',
            'region'            => 'exists:regiones,idRegion',
            'comuna'            => 'exists:comunas,idComuna'
        ]);
        $codigoCliente = $this->generarCodigoCliente();

        $id_proyecto = ProyectoUsuarioService::getIdProyecto();

        $ubicacion = DB::table('ubicaciones_geograficas')->insertGetId([
            'idProyecto'    => $id_proyecto,
            'codigoCliente' => $codigoCliente,
            'descripcion'   => $request->descripcion,
            'direccion'     => $request->direccion,
            'region'        => $request->region,
            'comuna'        => $request->comuna,
            'newApp'        => 1,
            'modo'          => 'ONLINE'
        ]);

        if (!$ubicacion) {
            return response()->json([
                'status'  => 'error',
                'message' => 'No se pudo crear la ubicación',
                'code'    => 422
            ], 422);
        }

        $usuarios = DB::table('inv_ciclos_usuarios')
            ->where('ciclo_id', $request->ciclo_auditoria)
            ->where('id_proyecto', $id_proyecto)
            ->pluck('usuario');

        foreach ($usuarios as $usuario) {
            DB::table('puntos_usuario')->insert([
                'idUbicacionGeo'  => $ubicacion,
                'login'           => $usuario,
                'fechaAsignacion' => now(),
                'estado'          => 1,
                'totalBienes'     => 0,
                'id_proyecto'     => $id_proyecto
            ]);
        }

        DB::table('inv_ciclos_puntos')->insert([
            'idCiclo'           => $request->ciclo_auditoria,
            'idPunto'           => $ubicacion,
            'usuario'           => $request->user()->name,
            'fechaCreacion'     => now()->format('Y-m-d'),
            'id_estado'         => 1,
            'auditoria_general' => 0,
            'modo'              => 'ONLINE',
        ]);

        $ciclo = $request->ciclo_auditoria;
        $cicloObj = InvCiclo::find($ciclo);
        $puntos = $cicloObj->puntos()->get();

        foreach ($puntos as $punto) {
            $punto->requireZonas = 1;
            $punto->cycle_id = $ciclo;

            if ($cicloObj->idTipoCiclo == 2) {
                $InvCicloPunto = InvCicloPunto::where('idCiclo', $ciclo)
                    ->where('idPunto', $punto->idUbicacionGeo)
                    ->first();

                $punto->auditoria_general = $InvCicloPunto->auditoria_general ?? 0;
            }
        }

        return response()->json(UbicacionGeograficaResource::collection($puntos), 200);
    }

    public function generarCodigoCliente()
    {

        $ultimoCodigo = DB::table('ubicaciones_geograficas')
            ->whereRaw('codigoCliente REGEXP "^[0-9]+$"')
            ->orderByRaw('CAST(codigoCliente AS UNSIGNED) DESC')
            ->value('codigoCliente');

        if ($ultimoCodigo) {
            $nuevoCodigo = intval($ultimoCodigo) + 1;
        } else {
            $nuevoCodigo = 1;
        }

        return str_pad($nuevoCodigo, 4, '0', STR_PAD_LEFT);
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
     * Display assets of the specified resource.
     *
     * @param   int $ciclo 
     * @param   int $punto
     * @param   \Illuminate\Http\Request
     * @return  \Illuminate\Http\Response
     */
    public function showAssets(int $ciclo, int $punto, Request $request)
    {

        $addressObj = UbicacionGeografica::find($punto);

        if (!$addressObj) {
            return response()->json(['status' => 'error', 'code' => 404], 404);
        }


        $cicloObj = InvCiclo::find($ciclo);

        if (!$cicloObj) {
            return response()->json(['status' => 'error', 'code' => 404], 404);
        }

        if ($cicloObj->puntos()->where('idUbicacionGeo', $punto)->count() === 0) {
            return response()->json(['status' => 'error', 'code' => 404, 'message' => 'La direccion no se corresponde con el ciclo'], 404);
        }


        $queryBuilder = Inventario::queryBuilderInventory_FindInGroupFamily_Pagination($addressObj, $cicloObj, $request);

        if ($request->adjusted_geo || $request->adjusted_geo == 0) {
            if ($request->adjusted_geo == 0) {

                $queryBuilder->where(function ($query) {
                    $query->whereNull('inv_inventario.adjusted_lat')->orWhereNull('inv_inventario.adjusted_lng');
                });
            } else if ($request->adjusted_geo == 1) {

                $queryBuilder->where(function ($query) {
                    $query->whereNotNull('inv_inventario.adjusted_lat')->whereNotNull('inv_inventario.adjusted_lng');
                });
            }
        }

        $assets = $queryBuilder->get();

        //
        return response()->json([
            'status' => 'OK',
            'data' => InventariosResource::collection($assets)
        ]);
    }

    public function showAssetsbyCycle(int $ciclo, Request $request)
    {
        $cicloObj = InvCiclo::find($ciclo);

        if (!$cicloObj) {
            return response()->json(['status' => 'error', 'code' => 404], 404);
        }

        $puntos = $cicloObj->puntos()->get();

        if ($puntos->isEmpty()) {
            return response()->json(['status' => 'error', 'code' => 404, 'message' => 'No hay puntos asociados al ciclo'], 404);
        }

        $queryBuilder = Inventario::queryBuilderInventory_FindInGroupFamily_Pagination($cicloObj, $cicloObj, $request);

        $assets = $queryBuilder->get();

        //
        return response()->json([
            'status' => 'OK',
            'data' => InventariosResource::collection($assets)
        ]);

        // return response()->json([
        //     'status' => 'OK',
        //     'data' => InventariosResource::collection($assets)
        // ]);
    }



    /**
     * Display families of the specified resource.
     *
     * @param   int $ciclo 
     * @param   int $punto
     * @param   \Illuminate\Http\Request
     * @return  \Illuminate\Http\Response
     */
    public function showGroupFamilies(int $ciclo, int $punto, Request $request)
    {

        $addressObj = UbicacionGeografica::find($punto);

        if (!$addressObj) {
            return response()->json(['status' => 'error', 'code' => 404], 404);
        }


        $cicloObj = InvCiclo::find($ciclo);

        if (!$cicloObj) {
            return response()->json(['status' => 'error', 'code' => 404], 404);
        }

        if ($cicloObj->puntos()->where('idUbicacionGeo', $punto)->count() === 0) {
            return response()->json(['status' => 'error', 'code' => 404, 'message' => 'El punto no se corresponde con el ciclo'], 404);
        }


        $queryBuilder = $addressObj->inv_group_families()->where('inv_inventario.id_ciclo', $cicloObj->idCiclo);;

        $family_place_resumen = $queryBuilder->get();



        //
        return response()->json([
            'status' => 'OK',
            'data' => GroupFamilyPlaceResumenResource::make($family_place_resumen)
        ]);
    }

    public function showGroupFamiliesByCycle(int $ciclo, Request $request)
    {
        $cicloObj = InvCiclo::find($ciclo);

        if (!$cicloObj) {
            return response()->json(['status' => 'error', 'code' => 404], 404);
        }

        $puntos = $cicloObj->puntos()->get();

        if ($puntos->isEmpty()) {
            return response()->json(['status' => 'error', 'code' => 404, 'message' => 'No hay puntos asociados al ciclo'], 404);
        }

        $family_place_resumen = collect();

        foreach ($puntos as $punto) {
            $addressObj = UbicacionGeografica::find($punto->idUbicacionGeo);

            if ($addressObj) {
                $queryBuilder = $addressObj->inv_group_families()->where('inv_inventario.id_ciclo', $cicloObj->idCiclo);
                $family_place_resumen = $family_place_resumen->concat($queryBuilder->get());
            }
        }

        return response()->json([
            'status' => 'OK',
            'data' => GroupFamilyPlaceResumenResource::make($family_place_resumen)
        ]);
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
        $puntoObj->general = 1;


        //
        return response()->json(UbicacionGeograficaResource::make($puntoObj));
    }


    public function showAllCycle(Request $request, int $ciclo)
    {
        $cicloObj = InvCiclo::find($ciclo);

        if (!$cicloObj) {
            return response()->json(['status' => 'NOK', 'code' => 404], 404);
        }

        $puntos = $cicloObj->puntos()->get();

        if ($puntos->isEmpty()) {
            return response()->json(['status' => 'NOK', 'code' => 404], 404);
        }

        foreach ($puntos as $punto) {
            $punto->requireActivos = 1;
            $punto->cycle_id = $cicloObj->idCiclo;
            $punto->general = 1;
        }

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

        $user = Auth::user();

        $usuario = $user?->name;
        $puntos = $cicloObj->ciclo_puntos_users($usuario, $ciclo)->get();

        if ($puntos->isEmpty()) {
            $puntos = $cicloObj->puntos()->get();
        }

        //$zonas = $cicloObj->zonesWithCats()->pluck('zona')->toArray();
        //¿La zona tiene bienes que no están asociados a emplazamientos?

        foreach ($puntos as $punto) {
            //$punto->zonas_cats = $zonas;
            $punto->requireZonas = 1;
            $punto->cycle_id = $ciclo;
            //Si el ciclo es auditoría y la auditoría es general, el atributo auditoria_general se pone a 1
            if ($cicloObj->idTipoCiclo == 2) {

                $InvCicloPunto = InvCicloPunto::where('idCiclo', $ciclo)->where('idPunto', $punto->idUbicacionGeo)->where('usuario', $usuario)->first();

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
