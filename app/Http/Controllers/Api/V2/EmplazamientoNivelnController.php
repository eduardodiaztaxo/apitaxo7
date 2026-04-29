<?php

namespace App\Http\Controllers\Api\V2;

use App\Http\Controllers\Controller;
use App\Http\Resources\V2\Inventario\EmplazamientoNnResource;
use App\Models\InvCiclo;
use App\Models\Inventario\EmplazamientoNn;
use App\Rules\SubLevelPlaceRule;
use Illuminate\Http\Request;

class EmplazamientoNivelnController extends Controller
{

    /**
     * Display the specified resource.
     * @param \Illuminate\Http\Request $request
     * @param int $ciclo
     * @param int $level
     * @param string $codigoUbicacion
     * @param int $address_id
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request, int $ciclo, int $level, int $address_id, string $codigoUbicacion)
    {

        $this->validateCodigoSubnivel($request, $codigoUbicacion, $level);

        $cycleObj = InvCiclo::find($ciclo);

        if (!$cycleObj) {
            return response()->json(['status' => 'error', 'message' => 'Not Found Cycle', 'code' => 404], 404);
        }

        $table = 'ubicaciones_n' . $level;
        $emplaObj = EmplazamientoNn::fromTable($table)->where('codigoUbicacion', '=', $codigoUbicacion)->where('idAgenda', '=', $address_id)->first();

        if (!$emplaObj) {
            return response()->json(['status' => 'error', 'code' => 404], 404);
        }


        $emplaObj->requirePunto = 1;

        $resource = new EmplazamientoNnResource($emplaObj, $cycleObj, $level);

        return response()->json(['status' => 'OK', 'data' => $resource], 200);
    }

    protected function validateCodigoSubnivel(Request $request,  string $codigo, int $subnivel)
    {
        $request->merge([
            'parentCode' => $codigo,
            'nivel' => $subnivel,
        ]);



        $request->validate([
            'parentCode' => ['required', 'string', new SubLevelPlaceRule($request->nivel)],
            'nivel' => ['required', 'integer'],
        ]);
    }
}
