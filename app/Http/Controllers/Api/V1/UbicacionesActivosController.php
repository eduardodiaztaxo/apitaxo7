<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\UbicacionGeograficaResource;
use App\Models\InvCiclo;
use App\Models\UbicacionGeografica;
use App\Services\ActivoService;
use Illuminate\Http\Request;
use App\Models\InvCicloPunto;
use App\Models\Inventario;
use Illuminate\Support\Facades\DB;


class UbicacionesActivosController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
        return response()->json(
            UbicacionGeograficaResource::collection(UbicacionGeografica::all()),
            200
        );
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
    public function show($id)
    {
        //
    }

    /**
     * Show labels by cycle and categories.
     *
     * @param  int  $ciclo
     * @param  int  $emplazamiento 
     * @return \Illuminate\Http\Response
     */
    public function showOnlyLabelsByCycleCats(int $ciclo, int $punto)
    {
        $puntoObj = UbicacionGeografica::find($punto);

        if (!$puntoObj) {
            return response()->json(['status' => 'NOK', 'code' => 404], 404);
        }

        $cicloObj = InvCiclo::find($ciclo);

        if (!$cicloObj) {
            return response()->json(['status' => 'NOK', 'message' => 'Ciclo no encontrado', 'code' => 404], 404);
        }

        $etiquetas = ActivoService::getLabelsByCycleAndAddress($puntoObj, $cicloObj);



        return response()->json($etiquetas, 200);
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

    /**
 * Move address for a label according to cycle
 * Mueve la dirección (punto) de una etiqueta según el ciclo en inv_inventario
 * y pone los campos de emplazamientos en 0
 *
 * @param  int  $cycle_id
 * @param  int  $address_id
 * @param  string  $etiqueta
 * @return \Illuminate\Http\Response
 */
public function moveAddress($cycle_id, $address_id, $etiqueta)
{
    // Validar que el ciclo existe
    $ciclo = InvCiclo::find($cycle_id);
    if (!$ciclo) {
        return response()->json([
            'status' => 'NOK',
            'message' => 'Ciclo no encontrado',
            'code' => 404
        ], 404);
    }

    // Validar que el punto (dirección) existe
    $punto = UbicacionGeografica::find($address_id);
    if (!$punto) {
        return response()->json([
            'status' => 'NOK',
            'message' => 'Punto (dirección) no encontrado',
            'code' => 404
        ], 404);
    }

    // Validar que el ciclo y el punto estén relacionados
    $cicloPunto = InvCicloPunto::where('idCiclo', $cycle_id)
        ->where('idPunto', $address_id)
        ->first();

    if (!$cicloPunto) {
        return response()->json([
            'status' => 'NOK',
            'message' => 'El punto no está asociado al ciclo',
            'code' => 404
        ], 404);
    }

    // Verificar que existe el inventario con esa etiqueta y ciclo
    $inventario = Inventario::where('etiqueta', $etiqueta)
        ->where('id_ciclo', $cycle_id)
        ->first();

    if (!$inventario) {
        return response()->json([
            'status' => 'NOK',
            'message' => 'No se encontró inventario con esa etiqueta para el ciclo especificado',
            'code' => 404
        ], 404);
    }

    // Preparar los datos de actualización
    // Mover la etiqueta al nuevo punto (address_id) y poner campos de emplazamientos en 0
    $updateData = [
        'idUbicacionGeo' => $address_id,
        'codigoUbicacion_N1' => 0,
        'idUbicacionN2' => 0,
        'codigoUbicacion_N2' => 0,
        'idUbicacionN3' => 0,
        'codigoUbicacionN3' => 0,
        'codigoUbicacionN4' => 0,
        'modo' => 'ONLINE',
    ];

    // Actualizar el inventario
    $updated = DB::table('inv_inventario')
        ->where('etiqueta', $etiqueta)
        ->where('id_ciclo', $cycle_id)
        ->update($updateData);

    if ($updated === 0) {
        return response()->json([
            'status' => 'NOK',
            'message' => 'Error al actualizar los datos del inventario o no se realizó ningún cambio',
            'code' => 500
        ], 500);
    }

    // Recargar el inventario actualizado
    $inventario->refresh();

    return response()->json([
        'status' => 'OK',
        'code' => 200,
        'message' => 'Dirección movida exitosamente',
        'data' => [
            'cycle_id' => $cycle_id,
            'address_id' => $address_id,
            'etiqueta' => $etiqueta,
            'inventario' => $inventario
        ]
    ], 200);
}
}
