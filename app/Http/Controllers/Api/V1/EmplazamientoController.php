<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\EmplazamientoResource;
use App\Models\CrudActivo;
use App\Models\Emplazamiento;
use App\Models\ZonaPunto;
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
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create(Request $request)
    {
        $validatedData = $request->validate([
            'nombre_emplazamiento' => 'required|string',
            'nombre_zona' => 'required|string',
            'idAgenda' => 'required|exists:ubicaciones_n1,idAgenda',  
        ]);
        
        $idAgenda = $validatedData['idAgenda'];

        $zona = [
            'descripcionUbicacion' => $validatedData['nombre_zona'],
            'idAgenda' => $idAgenda,
            'codigoUbicacion' => $this->generateCodigoUbicacion($idAgenda),
        ];
    
        $emplaObj = [
            'descripcionUbicacion' => $validatedData['nombre_emplazamiento'],
            'idAgenda' => $idAgenda,
        ];
    
        try {
            DB::beginTransaction();
    
            // tabla padre
            DB::table('ubicaciones_n1')->insert($zona);

            $zonaCodigo = DB::table('ubicaciones_n1')
                            ->where('idAgenda', $idAgenda)
                            ->orderBy('codigoUbicacion', 'desc')
                            ->first();
    
            if ($zonaCodigo && $zonaCodigo->codigoUbicacion) {
                $codigoUbicacionN2 = $zonaCodigo->codigoUbicacion . '01';
            } else {
                throw new \Exception("No se encontró un código de ubicación válido en la tabla padre.");
            }

            $emplaObj['codigoUbicacion'] = $codigoUbicacionN2;
    
            DB::table('ubicaciones_n2')->insert($emplaObj);
    
            DB::commit();
    
            return response()->json([
                'status' => 'OK',
                'code' => 200,
                'message' => 'Successfully processed',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'code' => 500,
                'message' => 'Error al crear datos: ' . $e->getMessage(),
            ], 500);
        }
    }
    
    private function generateCodigoUbicacion($idAgenda)
    {
        $lastCodigo = DB::table('ubicaciones_n1')
                        ->where('idAgenda', $idAgenda)
                        ->orderBy('codigoUbicacion', 'desc')
                        ->first();
        $newCodigo = $lastCodigo ? (intval($lastCodigo->codigoUbicacion) + 1) : 1;
        return str_pad($newCodigo, 2, '0', STR_PAD_LEFT); 
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
        'nombre_emplazamiento' => 'required|string|max:255', 
        'nombre_zona' => 'required|string|max:255',        
        'zona_id' => 'required|exists:ubicaciones_n1,idUbicacionN1',
        'id_agenda' => 'required|exists:ubicaciones_n1,idAgenda',
    ]);

    $emplaObj->descripcionUbicacion = $validatedData['nombre_emplazamiento'];
    $emplaObj->save();

    $zona = ZonaPunto::find($validatedData['zona_id']);

    if ($zona) {
        $zona->descripcionUbicacion = $validatedData['nombre_zona'];
        $zona->save();
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
