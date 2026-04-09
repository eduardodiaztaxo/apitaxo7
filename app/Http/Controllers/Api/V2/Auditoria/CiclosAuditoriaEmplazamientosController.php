<?php

namespace App\Http\Controllers\Api\V2\Auditoria;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\EmplazamientoNivel1Resource;
use App\Models\Auditoria\EmplazamientoNn;
use App\Models\InvCiclo;
use App\Rules\SubLevelPlaceRule;
use Illuminate\Http\Request;

class CiclosAuditoriaEmplazamientosController extends Controller
{
    //
    /**
     * Display the specified resource.
     *
     * @param \Illuminate\Http\Request $request
     * @param  int  $ciclo
     * @return \Illuminate\Http\Response
     */
    public function showByCycleAndGrupFamily(Request $request, int $ciclo, int $punto, string $codigo, int $subnivel)
    {
        $this->validateCodigoSubnivel($request, $codigo, $subnivel);

        $cycleObj = InvCiclo::find($ciclo);

        if (!$cycleObj) {
            return response()->json(['status' => 'NOK', 'code' => 404], 404);
        }

        $queryBuilder = EmplazamientoNn::fromTable('ubicaciones_n' . $subnivel)->where('idAgenda', '=', $punto);

        if ($subnivel > 1) {
            $queryBuilder = $queryBuilder->where('codigo', 'like', $codigo . '%');
        }

        $emplazamientos = $queryBuilder->get();



        return response()->json(EmplazamientoNivel1Resource::collection($emplazamientos), 200);

        return response()->json(['status' => 'OK', 'data' => $resources], 200);
    }

    protected function validateCodigoSubnivel(Request $request,  string $codigo, int $subnivel)
    {
        $request->merge([
            'codigo' => $codigo,
            'subnivel' => $subnivel,
        ]);



        $request->validate([
            'codigo' => ['required', 'string', new SubLevelPlaceRule($request->subnivel)],
            'subnivel' => ['required', 'integer'],
        ]);
    }
}
