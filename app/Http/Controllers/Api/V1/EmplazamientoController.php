<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\EmplazamientoResource;
use App\Http\Resources\V1\EmplazamientoNivel3Resource;
use App\Models\CrudActivo;
use App\Models\Emplazamiento;
use App\Models\EmplazamientoN3;
use App\Models\ZonaPunto;
use App\Services\PlaceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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
     * Create new resource.
     *
     * @param  \Illuminate\Http\Request     $request
     * @return \Illuminate\Http\Response
     */
    public function create(Request $request) {}





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
            'zona_id'       => 'required|exists:ubicaciones_n1,idUbicacionN1',
            'agenda_id'     => 'required|exists:ubicaciones_n1,idAgenda',
            'estado'        => 'sometimes|required|in:0,1',
            'ciclo_auditoria' => 'required'
        ]);

        $zona = ZonaPunto::find($request->zona_id);

        $placeService = new PlaceService();
        $cicloAuditoria = $request->ciclo_auditoria;
        $code = $placeService->getNewEmplaCode($zona);
    
        $data = [
            'idAgenda'              => $request->agenda_id,
            'descripcionUbicacion'  => $request->descripcion,
            'codigoUbicacion'       => $code,
            'estado'                => $request->estado !== null ? $request->estado : 1,
            'usuario'               => $request->user()->name,
            'ciclo_auditoria'       => $cicloAuditoria
        ];
    
        $empla = Emplazamiento::create($data);
    
        Emplazamiento::create([
            'idAgenda' => $request->codigoUbicacion,
            'ciclo_auditoria' => $cicloAuditoria
        ]);
    
        if (!$empla) {
            return response()->json([
                'status' => 'error',
                'No se pudo crear el emplazamiento',
                422
            ], 422);
        }
    
        return response()->json([
            'status'    => 'OK',
            'message'   => 'Creado exitosamente',
            'data'      => EmplazamientoResource::make($empla)
        ]);
    }

    /**
     * Create sub emplazamientos.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
public function createSubEmplazamientos(Request $request)
{
    $request->validate([
        'descripcion'      => 'required|string',
        'agenda_id'        => 'required|exists:ubicaciones_n2,idAgenda',
        'codigoUbicacion'  => 'required|exists:ubicaciones_n2,codigoUbicacion'
    ]);

    $baseCodigo = $request->codigoUbicacion; 

    $subCodigos = DB::table('ubicaciones_n3')
        ->where('codigoUbicacion', 'like', $baseCodigo . '%')
        ->pluck('codigoUbicacion');

    $maxSecuencia = $subCodigos
        ->map(function ($codigo) use ($baseCodigo) {
            return intval(substr($codigo, strlen($baseCodigo), 2));
        })
        ->max();

    $nuevoSufijo = str_pad(($maxSecuencia + 1), 2, '0', STR_PAD_LEFT); 
    $nuevoCodigoUbicacionN3 = $baseCodigo . $nuevoSufijo;

    $data = [
        'idAgenda'             => $request->agenda_id,
        'descripcionUbicacion' => $request->descripcion,
        'codigoUbicacion'      => $nuevoCodigoUbicacionN3,
        'usuario'              => $request->user()->name,
        'estado'               => 1,
        'newApp'               => 1
    ];

    $empla = EmplazamientoN3::create($data);

    if (!$empla) {
        return response()->json([
            'status'  => 'error',
            'message' => 'No se pudo crear el emplazamiento'
        ], 422);
    }

    return response()->json([
        'status'  => 'OK',
        'message' => 'Creado exitosamente',
        'data'    => $empla
    ]);
}

public function createSubEmplazamientosNivel3(Request $request)
{
    $request->validate([
        'descripcion'      => 'required|string',
        'agenda_id'        => 'required|exists:ubicaciones_n3,idAgenda',
        'codigoUbicacion'  => 'required|exists:ubicaciones_n3,codigoUbicacion'
    ]);

    $baseCodigo = $request->codigoUbicacion; 

    $subCodigos = DB::table('ubicaciones_n4')
        ->where('codigoUbicacion', 'like', $baseCodigo . '%')
        ->pluck('codigoUbicacion');

    $maxSecuencia = $subCodigos
        ->map(function ($codigo) use ($baseCodigo) {
            return intval(substr($codigo, strlen($baseCodigo), 2));
        })
        ->max();

    $nuevoSufijo = str_pad(($maxSecuencia + 1), 2, '0', STR_PAD_LEFT); 
    $nuevoCodigoUbicacionN3 = $baseCodigo . $nuevoSufijo;

    $data = [
        'idAgenda'             => $request->agenda_id,
        'descripcionUbicacion' => $request->descripcion,
        'codigoUbicacion'      => $nuevoCodigoUbicacionN3,
        'usuario'              => $request->user()->name,
        'estado'               => 1,
        'newApp'               => 1
    ];

    $empla = EmplazamientoN3::create($data);

    if (!$empla) {
        return response()->json([
            'status'  => 'error',
            'message' => 'No se pudo crear el emplazamiento'
        ], 422);
    }

    return response()->json([
        'status'  => 'OK',
        'message' => 'Creado exitosamente',
        'data'    => $empla
    ]);
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
public function show(int $emplazamiento, int $ciclo)
{
    $emplaObj = Emplazamiento::find($emplazamiento);

    if ($emplaObj) {
        $resource = EmplazamientoResource::make($emplaObj);
    } else {
        $emplaObj = EmplazamientoN3::find($emplazamiento);

        if (!$emplaObj) {
            return response()->json(['status' => 'NOK', 'code' => 404], 404);
        }

        $resource = EmplazamientoNivel3Resource::make($emplaObj);
    }

    $emplaObj->requirePunto = 1;
    $emplaObj->requireActivos = 1;
    $emplaObj->cycle_id = $ciclo;

    return response()->json($resource);
}


public function showN3(string $codigoUbicacionN3)
{
    $exists = DB::table('ubicaciones_n2')
                ->where('codigoUbicacion', $codigoUbicacionN3)
                ->exists();

    if (!$exists) {
        return response()->json(['status' => 'NOK', 'code' => 404], 404);
    }

    $collection = DB::table('ubicaciones_n3')
                    ->where('codigoUbicacion', 'like', $codigoUbicacionN3 . '%')
                    ->get();

    $collection = $collection->map(function ($item) use ($codigoUbicacionN3) {
        $item->num_activos_cats_by_cycleN3 = DB::table('crud_activos')
            ->where('ubicacionOrganicaN3', 'like', $item->codigoUbicacion . '%')
            ->count();

        $item->num_activos_invN3 = DB::table('inv_inventario')
            ->where('codigoUbicacionN3', 'like', $item->codigoUbicacion . '%')
            ->count();

        return $item;
    });

    return response()->json($collection, 200);
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
    public function update(Request $request, int $id)
    {

        $emplaObj = Emplazamiento::find($id);
    
        if (!$emplaObj) {
            return response()->json([
                'status' => 'NOK',
                'code' => 404,
                'message' => 'Emplazamiento no encontrado'
            ], 404);
        }

        $validatedData = $request->validate([
            'nombre_emplazamiento' => 'string|max:255',
            'ubicacion_emplazamiento' => 'string|max:255',
            'zona_id' => 'required|exists:ubicaciones_n1,idUbicacionN1',
            'id_agenda' => 'required|exists:ubicaciones_n1,idAgenda',
        ]);
    
        $emplaObj->descripcionUbicacion = $validatedData['nombre_emplazamiento'];
        $emplaObj->save();
    
        $zona = ZonaPunto::find($validatedData['zona_id']);
    
        if ($zona) {
    
            $zona->descripcionUbicacion = $validatedData['ubicacion_emplazamiento'];  // Usando el 'ubicacion_emplazamiento' del request
            $zona->save();
        } else {
            return response()->json([
                'status' => 'NOK',
                'code' => 404,
                'message' => 'Zona no encontrada'
            ], 404);
        }
    
        return response()->json([
            'status' => 'OK',
            'message' => 'Emplazamiento y zona actualizados correctamente',
            'data' => EmplazamientoResource::make($emplaObj),
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
