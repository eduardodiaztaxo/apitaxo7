<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\EmplazamientoResource;
use App\Http\Resources\V2\EmplazamientoNivel2Resource;
use App\Http\Resources\V2\EmplazamientoNivel3Resource;
use App\Http\Resources\V1\EmplazamientoNivel1Resource;
use App\Http\Resources\V1\EmplazamientoAllResource;
use App\Models\CrudActivo;
use App\Models\InvCiclo;
use App\Models\Emplazamiento;
use App\Models\EmplazamientoN2;
use App\Models\EmplazamientoN1;
use App\Models\EmplazamientoN3;
use App\Models\Inventario;
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
            'descripcion'     => 'required|string',
            'zona_id'         => 'required|exists:ubicaciones_n1,codigoUbicacion',
            'agenda_id'       => 'required|exists:ubicaciones_geograficas,idUbicacionGeo',
            'estado'          => 'sometimes|required|in:0,1',
            'ciclo_auditoria' => 'required'
        ]);

        $idMax = DB::table('ubicaciones_n2')
            ->where('idAgenda', $request->agenda_id)
            ->where('codigoUbicacion', 'like', $request->zona_id . '%')
            ->max('codigoUbicacion');

        $zonaId = $request->zona_id;

        if ($idMax) {
            $num = substr($idMax, strlen($zonaId));
            $numIncrementado = str_pad((int)$num + 1, strlen($num), '0', STR_PAD_LEFT);
            $code = $zonaId . $numIncrementado;
        } else {
            $code = $zonaId . '01';
        }

        $data = [
            'idProyecto'            => $request->ciclo_auditoria,
            'idAgenda'              => $request->agenda_id,
            'descripcionUbicacion'  => $request->descripcion,
            'codigoUbicacion'       => $code,
            'fechaCreacion'         => date('Y-m-d H:i:s'),
            'estado'                => $request->estado !== null ? $request->estado : 1,
            'usuario'               => $request->user()->name,
            'ciclo_auditoria'       => $request->ciclo_auditoria,
            'newApp'                => 1,
            'modo'                  => 'ONLINE'
        ];

        $empla = EmplazamientoN2::create($data);

        if (!$empla) {
            return response()->json([
                'status' => 'error',
                'message' => 'No se pudo crear el emplazamiento'
            ], 422);
        }

        return response()->json([
            'status'  => 'OK',
            'message' => 'Creado exitosamente',
            'data'    => EmplazamientoNivel2Resource::make($empla)
        ]);
    }


    /**
     * Create sub emplazamientos.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    //este falla al crear uno nuevo e ingresar inmediatamente
    //fijar añadiendo propiedad zone_address
    public function createSubEmplazamientosNivel3(Request $request)
    {

        //return (ZonaPunto::where('idAgenda', '=', $request->agenda_id)->where('codigoUbicacion', '=', $request->codigoUbicacion)->toSql());

        $request->validate([
            'descripcion'      => 'required|string',
            'agenda_id'        => 'required|exists:ubicaciones_geograficas,idUbicacionGeo',
            'ciclo'            => 'required',
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
            'idProyecto'           => $request->ciclo,
            'idAgenda'             => $request->agenda_id,
            'descripcionUbicacion' => $request->descripcion,
            'codigoUbicacion'      => $nuevoCodigoUbicacionN3,
            'usuario'              => $request->user()->name,
            'estado'               => 1,
            'fechaCreacion'        => date('Y-m-d H:i:s'),
            'newApp'               => 1,
            'modo'                 => 'ONLINE'
        ];

        $num_activos_cats_by_cycleN3 = DB::table('crud_activos')
            ->where('ubicacionOrganicaN4', $nuevoCodigoUbicacionN3)
            ->count();

        $num_activos_invN3 = DB::table('inv_inventario')
            ->where('codigoUbicacionN3', $nuevoCodigoUbicacionN3)
            ->count();

        $empla = EmplazamientoN3::create($data);

        if (!$empla) {
            return response()->json([
                'status'  => 'error',
                'message' => 'No se pudo crear el emplazamiento'
            ], 422);
        }

        $zone_address = ZonaPunto::where('idAgenda', '=', $empla->idAgenda)
            ->where('codigoUbicacion', '=', substr($empla->codigoUbicacion, 0, 2))->first();

          return response()->json([
            'status'  => 'OK',
            'message' => 'Creado exitosamente',
            'data'    => EmplazamientoNivel3Resource::make($empla)
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


    public function show(int $emplazamiento, int $ciclo, string $codigoUbicacion)
    {
        // Nivel 1
        if (strlen($codigoUbicacion) === 2) {
            $emplaObj = EmplazamientoN1::where('idUbicacionN1', $emplazamiento)
                ->where('codigoUbicacion', $codigoUbicacion)
                ->first();

            if (!$emplaObj) {
                return response()->json(['status' => 'NOK', 'code' => 404], 404);
            }

            $resource = EmplazamientoNivel1Resource::make($emplaObj);
        }
        // Nivel 2
        else if (strlen($codigoUbicacion) === 4) {
            $emplaObj = Emplazamiento::where('idUbicacionN2', $emplazamiento)
                ->where('codigoUbicacion', $codigoUbicacion)
                ->first();

            if (!$emplaObj) {
                return response()->json(['status' => 'NOK', 'code' => 404], 404);
            }

            $resource = EmplazamientoResource::make($emplaObj);
        }
        // Nivel 3
        else {
            $emplaObj = EmplazamientoN3::where('idUbicacionN3', $emplazamiento)
                ->where('codigoUbicacion', $codigoUbicacion)
                ->first();

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

    public function showTodos(int $idAgenda, int $ciclo)
    {
        $emplaObj = EmplazamientoN1::with(['emplazamientosN2', 'emplazamientosN3'])
            ->where('idAgenda', $idAgenda)
            ->first();

        if (!$emplaObj) {
            return response()->json(['status' => 'NOK', 'code' => 404], 404);
        }

        $emplaObj->requirePunto = 1;
        $emplaObj->requireActivos = 1;
        $emplaObj->cycle_id = $ciclo;

        return response()->json(EmplazamientoAllResource::make($emplaObj));
    }


    public function groupEmplazamientos(int $idAgenda, int $ciclo)
    {
        $cicloObj = InvCiclo::find($ciclo);

        if (!$cicloObj) {
            return response()->json([
                'status' => 'NOK',
                'message' => 'Ciclo no encontrado',
                'code' => 404
            ], 404);
        }

        $activos = $cicloObj->activos_with_cats_by_cycle_emplazamiento($ciclo, $idAgenda);

        return response()->json([
            'status' => 'OK',
            'message' => 'Emplazamientos obtenidos correctamente',
            'data' => [
                'emplazamientos' => $activos,
            ],
        ], 200);
    }

public function groupEmplazamientosPorOt(int $ciclo)
{
    $cicloObj = InvCiclo::find($ciclo);

    if (!$cicloObj) {
        return response()->json([
            'status' => 'NOK',
            'message' => 'Ciclo no encontrado',
            'code' => 404
        ], 404);
    }

    $activos = $cicloObj->activos_with_cats_by_cycle_emplazamiento_por_ot($ciclo);

    return response()->json([
        'status' => 'OK',
        'message' => 'Emplazamientos obtenidos correctamente',
        'data' => [
            'emplazamientos' => $activos
        ],
    ], 200);
}

    public function groupMapDireccionDiferencias(int $idAgenda, int $ciclo)
{
    $cicloObj = InvCiclo::find($ciclo);

    if (!$cicloObj) {
        return response()->json([
            'status' => 'NOK',
            'message' => 'Ciclo no encontrado',
            'code' => 404
        ], 404);
    }

    $diferencias = $cicloObj->diferencias_por_direcciones($ciclo, $idAgenda);
    $total = array_sum(array_map(function($item) {
        return $item->q_teorico ?? 0;
    }, $diferencias));


    return response()->json([
        'status' => 'OK',
        'message' => 'Diferencias obtenidas correctamente',
        'data' => [
            $diferencias,
            $total
        ],
    ], 200);
}

public function groupMapDiferenciasOT(int $ciclo)
{
    $cicloObj = InvCiclo::find($ciclo);

    if (!$cicloObj) {
        return response()->json([
            'status' => 'NOK',
            'message' => 'Ciclo no encontrado',
            'code' => 404
        ], 404);
    }

    $puntos = $cicloObj->puntos()->pluck('idUbicacionGeo')->toArray();

    if (empty($puntos)) {
        return response()->json(['status' => 'NOK', 'code' => 404], 404);
    }

    $diferencias = $cicloObj->diferencias_por_puntos_OT($puntos);
    $total = array_sum(array_map(function($item) {
        return $item->q_teorico ?? 0;
    }, $diferencias));

    return response()->json([
        'status' => 'OK',
        'message' => 'Diferencias por OT obtenidas correctamente',
        'data' => [
            $diferencias,
            $total
        ],
    ], 200);
}
    public function moverEmplazamientos(Request $request, string $codigoUbicacion, int $ciclo_id, int $agenda_id, string $etiqueta)
    {
        // Nivel 1
        if (strlen($codigoUbicacion) === 2) {
            $emplaObj = EmplazamientoN1::where('idAgenda', $agenda_id)
                ->where('codigoUbicacion', $codigoUbicacion)
                ->first();

            if (!$emplaObj) {

                return response()->json([
                    'status' => 'NOK',
                    'code' => 404,
                    'data' => [
                        'idAgenda' => $agenda_id,
                        'codigoUbicacion' => $codigoUbicacion
                    ]
                ], 404);
            }

            $idEmplazamiento = $emplaObj->idUbicacionN1;
            $nombre = $emplaObj->descripcionUbicacion;
            $codigoUbicacion = (string) $emplaObj->codigoUbicacion;
        }
        // Nivel 2
        else if (strlen($codigoUbicacion) === 4) {
            $emplaObj = Emplazamiento::where('idAgenda', $agenda_id)
                ->where('codigoUbicacion', $codigoUbicacion)
                ->first();

            if (!$emplaObj) {

                return response()->json([
                    'status' => 'NOK',
                    'code' => 404,
                    'data' => [
                        'idAgenda' => $agenda_id,
                        'codigoUbicacion' => $codigoUbicacion
                    ]
                ], 404);
            }

            $idEmplazamiento = $emplaObj->idUbicacionN2;
            $nombre = $emplaObj->descripcionUbicacion;
            $codigoUbicacion = (string) $emplaObj->codigoUbicacion;
        }
        // Nivel 3
        else {
            $emplaObj = EmplazamientoN3::where('idAgenda', $agenda_id)
                ->where('codigoUbicacion', $codigoUbicacion)
                ->first();

            if (!$emplaObj) {

                return response()->json([
                    'status' => 'NOK',
                    'code' => 404,
                    'data' => [
                        'idAgenda' => $agenda_id,
                        'codigoUbicacion' => $codigoUbicacion
                    ]
                ], 404);
            }

            $idEmplazamiento = $emplaObj->idUbicacionN3;
            $nombre = $emplaObj->descripcionUbicacion;
            $codigoUbicacion = (string) $emplaObj->codigoUbicacion;
        }


        $updateData = [
            'modo' => 'ONLINE',
        ];


        if (strlen($codigoUbicacion) === 2) {
            $updateData['codigoUbicacion_N1'] = $codigoUbicacion;
            $updateData['idUbicacionN2'] = 0;
            $updateData['codigoUbicacion_N2'] = 0;
            $updateData['idUbicacionN3'] = 0;
            $updateData['codigoUbicacionN3'] = 0;
        } elseif (strlen($codigoUbicacion) === 4) {
            $updateData['codigoUbicacion_N1'] = 0;
            $updateData['idUbicacionN2'] = $emplaObj->idUbicacionN2;
            $updateData['codigoUbicacion_N2'] = $codigoUbicacion;
            $updateData['idUbicacionN3'] = 0;
            $updateData['codigoUbicacionN3'] = 0;
        } elseif (strlen($codigoUbicacion) === 6) {
            $updateData['codigoUbicacion_N1'] = 0;
            $updateData['idUbicacionN2'] = 0;
            $updateData['codigoUbicacion_N2'] = 0;
            $updateData['idUbicacionN3'] = $emplaObj->idUbicacionN3;
            $updateData['codigoUbicacionN3'] = $codigoUbicacion;
        }


        $updated = DB::table('inv_inventario')
            ->where('etiqueta', $etiqueta)
            ->where('id_ciclo', $ciclo_id)
            ->update($updateData);

        if ($updated === 0) {
            return response()->json([
                'status' => 'NOK',
                'message' => 'Error al actualizar los datos del inventario o no se realizó ningún cambio',
                'code' => 500
            ], 500);
        }

        $bien = Inventario::where('etiqueta', '=', $etiqueta)->first();

        $bien->fillCodeAndIDSEmplazamientos();

        return response()->json([
            [
                'id' => $idEmplazamiento,
                'ciclo_id' => $ciclo_id,
                'codigoUbicacion' => $codigoUbicacion,
                'nombre' => $nombre,
                'bien' => $bien
            ]
        ]);
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
    $codigo_ubicacion_n1 = $request->input('codigo_ubicacion_n1');
    $codigo_ubicacion_sub_nivel = $request->input('codigo_ubicacion_sub_nivel');
    $id_agenda = $request->input('id_agenda');

    $validatedData = $request->validate([
        'nombre_emplazamiento' => 'string|max:255',
        'ubicacion_emplazamiento' => 'string|max:255',
        'zona_id' => 'required|exists:ubicaciones_n1,idUbicacionN1',
        'id_agenda' => 'required|exists:ubicaciones_n1,idAgenda',
    ]);

    $updated = [];

    $emplaN1 = EmplazamientoN1::where('codigoUbicacion', $codigo_ubicacion_n1)
        ->where('idAgenda', $id_agenda)
        ->first();

    if ($emplaN1) {
        $emplaN1->descripcionUbicacion = $validatedData['nombre_emplazamiento'];
        $emplaN1->save();
        $updated['nivel1'] = $emplaN1;
    }

    $emplaSub = null;
    if (!empty($codigo_ubicacion_sub_nivel)) {
        $length = strlen($codigo_ubicacion_sub_nivel);

        if ($length >= 6) {
            $emplaSub = EmplazamientoN3::where('codigoUbicacion', $codigo_ubicacion_sub_nivel)
                ->where('idAgenda', $id_agenda)
                ->first();
        } elseif ($length >= 4) {
            $emplaSub = EmplazamientoN2::where('codigoUbicacion', $codigo_ubicacion_sub_nivel)
                ->where('idAgenda', $id_agenda)
                ->first();
        }

        if ($emplaSub) {
            $emplaSub->descripcionUbicacion = $validatedData['nombre_emplazamiento'];
            $emplaSub->save();
            $updated['subnivel'] = $emplaSub;
        }
    }

    $zona = ZonaPunto::find($validatedData['zona_id']);
    if ($zona) {
        $zona->descripcionUbicacion = $validatedData['ubicacion_emplazamiento'];
        $zona->save();
        $updated['zona'] = $zona;
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
        'data' => $updated
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
