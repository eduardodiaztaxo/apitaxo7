<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\ZonaPuntoResource;
use App\Models\InvCiclo;
use App\Models\UbicacionGeografica;
use App\Http\Resources\V1\UbicacionGeograficaDireccionResource;
use App\Http\Resources\V1\UbicacionGeograficaResource;
use App\Services\ActivoFinderService;
use App\Services\ProyectoUsuarioService;
use App\Models\ZonaPunto;
use App\Services\PlaceService;
use Illuminate\Http\Request;
use App\Models\EmplazamientoLegacy;
use Illuminate\Support\Facades\DB;

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
        $request->validate([
            'descripcion'   => 'required|string',
            'punto_id'      => 'required|exists:ubicaciones_geograficas,idUbicacionGeo',
            'estado'        => 'sometimes|required|in:0,1',
            'ciclo_auditoria' => 'required'
        ]);

        $punto = UbicacionGeografica::find($request->punto_id);

        $placeService = new PlaceService();
        $cicloAuditoria = $request->ciclo_auditoria;
        $code = $placeService->getNewZoneCode($punto);

        $id_proyecto = ProyectoUsuarioService::getIdProyecto();

        $descripcion = strtoupper(trim(preg_replace('/\s+/', ' ', $request->descripcion)));
        $codigo = $descripcion;

        $exists = ZonaPunto::where('idAgenda', $request->punto_id)
            ->whereRaw(
                'UPPER(TRIM(REPLACE(REPLACE(REPLACE(descripcionUbicacion, "  ", " "), "  ", " "), "  ", " "))) = ?',
                [$descripcion]
            )
            ->exists();

        $existsEmplazamiento = EmplazamientoLegacy::where('id_agenda', $request->punto_id)
        ->where('id_padre', 0)
        ->where('nivel', 1)
        ->where('activo', 'S')
        ->whereRaw(
            'UPPER(TRIM(REPLACE(REPLACE(REPLACE(descripcion, "  ", " "), "  ", " "), "  ", " "))) = ?',
            [$descripcion]
        )
        ->exists();

        if ($exists || $existsEmplazamiento) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Ya existe una zona con esa descripción en este punto.'
            ], 422);
        }

        try{
            DB::beginTransaction();

            /*
            * 1. Crear primero en emplazamientos
            */
            $emplazamiento = EmplazamientoLegacy::create([
                'id_proyecto'         => $id_proyecto,
                'id_agenda'           => $request->punto_id,
                'id_padre'            => 0,
                'nivel'               => 1,
                'codigo_ubicacion'    => $code,
                'descripcion'         => $descripcion,
                'estado'              => 'ACTIVO',
                'activo'              => 'S',
                'fecha_creacion'      => date('Y-m-d H:i:s'),
                'fecha_actualizacion' => date('Y-m-d H:i:s'),
                'usuario'             => $request->user()->name,
                'codigo'              => $codigo,
                'ciclo_auditoria'     => $cicloAuditoria, // desde el mantenedor lo deja en cero
                'new_app'             => 1,
                'modo'                => 'ONLINE'
            ]);

            /*
            * 2. Crear relación self en emplazamientos_closure
            */
            DB::table('emplazamientos_closure')->insert([
                'ancestro'     => $emplazamiento->id_emplazamiento,
                'descendiente' => $emplazamiento->id_emplazamiento,
                'profundidad'  => 0,
            ]);

            /*
            * 3. Crear después en ubicaciones_n1
            */
            $data = [
                'idProyecto'            => $id_proyecto,
                'idAgenda'              => $request->punto_id,
                'descripcionUbicacion'  => $descripcion,
                'codigoUbicacion'       => $code,
                'codigo'                => $codigo,
                'estado'                => $request->estado !== null ? $request->estado : 1,
                'fechaCreacion'         => date('Y-m-d H:i:s'),
                'usuario'               => $request->user()->name,
                'ciclo_auditoria'       => $cicloAuditoria,
                'newApp'                => 1,
                'modo'                  => 'ONLINE'
            ];
            $zona = ZonaPunto::create($data);

            DB::commit();

            return response()->json([
                'status'  => 'OK',
                'message' => 'Creado exitosamente',
                'data'    => ZonaPuntoResource::make($zona)
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status'  => 'error',
                'message' => 'Error al crear la zona: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display zone resource
     *
     * @param  int  $zona
     * @return \Illuminate\Http\Response
     */

     public function show_Direccion(Request $request, int $zona, int $ciclo)
     {
         $zonaObj = UbicacionGeografica::find($zona);
     
         if (!$zonaObj) {
             return response()->json(['status' => 'NOK', 'message' => 'Zona no encontrada', 'code' => 404], 404);
         }
     
         // Configurar propiedades adicionales
         $zonaObj->requireAddress = 1;
         $zonaObj->requireOrphanAssets = 1;
         $zonaObj->cycle_id = $ciclo; // Asignar el ciclo al recurso
         // Agregar el ciclo al recurso

         return response()->json(UbicacionGeograficaDireccionResource::make($zonaObj), 200);
     }

     public function show(Request $request, int $zona)
     {

         $zonaObj = ZonaPunto::find($zona);
    
     
             if (!$zonaObj) {
        
                 return response()->json(['status' => 'NOK', 'message' => 'Zona no encontrada', 'code' => 404], 404);
             }
         
     
         // Configurar propiedades adicionales
         $zonaObj->requireAddress = 1;
         $zonaObj->requireOrphanAssets = 1;
     
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

        // if (!$count || $count === 0) {
        //     //Si la zona es nueva, creado en el ciclo
        //     if ($zonaObj->ciclo_auditoria !== $cicloObj->idCiclo) {
        //         return response()->json(['status' => 'NOK', 'message' => 'Zona no asociada al Ciclo', 'code' => 404], 404);
        //     }
        // }

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
        $emplaObj = UbicacionGeografica::find($id);
    
        if (!$emplaObj) {
            return response()->json([
                'status' => 'NOK',
                'code' => 404,
                'message' => 'Dirección no encontrado'
            ], 404);
        }

        $validatedData = $request->validate([
            'nombre_emplazamiento' => 'string|max:255',
        ]);
    
        $emplaObj->descripcion = $validatedData['nombre_emplazamiento'];
        $emplaObj->save();
    
        return response()->json([
            'status' => 'OK',
            'message' => 'Emplazamiento y zona actualizados correctamente',
            'data' => UbicacionGeograficaResource::make($emplaObj),
        ], 200);
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
