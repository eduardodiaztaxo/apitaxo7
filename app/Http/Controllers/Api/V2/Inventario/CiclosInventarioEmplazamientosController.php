<?php

namespace App\Http\Controllers\Api\V2\Inventario;

use App\Http\Controllers\Controller;
use App\Http\Resources\V2\Inventario\EmplazamientoNnResource;
use App\Models\InvCiclo;
use App\Models\Inventario\EmplazamientoNn;
use App\Rules\SubLevelPlaceRule;
use Illuminate\Http\Request;

class CiclosInventarioEmplazamientosController extends Controller
{


    /**
     * Display the specified resource.
     *
     * @param \Illuminate\Http\Request $request
     * @param  int  $cycle_id
     * @param  int  $address_id
     * @param  string  $parentCode
     * @param  int  $nivel
     * @return \Illuminate\Http\Response
     */
    public function showByCycleAndLevel(Request $request, int $cycle_id, int $address_id, string $parentCode, int $nivel)
    {
        //$this->validateCodigoSubnivel($request, $parentCode, $nivel);

        $cycleObj = InvCiclo::find($cycle_id);

        if (!$cycleObj) {
            return response()->json(['status' => 'NOK', 'code' => 404], 404);
        }

        $queryBuilder = EmplazamientoNn::fromTable('ubicaciones_n' . $nivel)->where('idAgenda', '=', $address_id);

        if ($nivel > 1) {
            $queryBuilder = $queryBuilder->where('codigoUbicacion', 'like', $parentCode . '%');
        }

        $emplazamientos = $queryBuilder->get();

        $resources = $emplazamientos->map(function ($emplazamiento) use ($cycleObj, $nivel) {
            return new EmplazamientoNnResource($emplazamiento, $cycleObj, $nivel);
        });



        return response()->json(['status' => 'OK', 'data' => $resources], 200);
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
