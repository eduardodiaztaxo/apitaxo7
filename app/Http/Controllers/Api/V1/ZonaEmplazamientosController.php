<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\EmplazamientoResource;
use App\Http\Resources\V1\EmplazamientoNivel3Resource;
use App\Http\Resources\V1\EmplazamientoNivel1Resource;
use App\Models\InvCiclo;
use App\Models\ZonaPunto;
use App\Models\UbicacionGeografica;
use App\Models\Region;
use App\Models\EmplazamientoN3;
use App\Models\EmplazamientoN1;
use App\Models\Emplazamiento;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ZonaEmplazamientosController extends Controller
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

 
    public function store(Request $request){
        
    }
    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request, int $zona)
    {
        $zonaObj = ZonaPunto::find($zona);

        if (!$zonaObj) {
            return response()->json(['status' => 'NOK', 'code' => 404], 404);
        }

        $emplazamientos = $zonaObj->emplazamientos()->get();

        return response()->json([
            'message' => $request->get('middleware_message'),
            'data' => EmplazamientoResource::collection($emplazamientos)
        ], 200);
    }


  public function showByCycleCats(Request $request, int $ciclo, string $zona, int $agenda_id)
{
    $zonaObjs = Emplazamiento::where('codigoUbicacion', 'like', $zona . '%')
                ->where('idAgenda', $agenda_id)
                ->get();

     $cicloObj = InvCiclo::find($ciclo);

        if (!$cicloObj) {
            return response()->json([
                'status' => 'NOK',
                'message' => 'Ciclo no encontrado',
                'code' => 404
            ], 404);
        }

        $emplazamientos = collect();

        foreach ($zonaObjs as $zonaObj) {
            $emplaCats = $cicloObj->zoneEmplazamientosWithCats($zonaObj)->pluck('idUbicacionN2')->toArray();



            $subEmplas = empty($emplaCats)
                ? $zonaObj->emplazamientos()->get()
                : $zonaObj->emplazamientos()->whereIn('idUbicacionN2', $emplaCats)->get();

            foreach ($subEmplas as $sub) {
                $sub->cycle_id = $ciclo;
                $emplazamientos->push($sub);
            }
        }


    return response()->json(EmplazamientoResource::collection($emplazamientos), 200);
}


    public function CycleCatsNivel3(Request $request, int $ciclo, string $zona, int $agenda_id)
    {


        $zonaObjs = EmplazamientoN3::where('codigoUbicacion', 'LIKE', $zona . '%')->where('idAgenda', '=', $agenda_id)->get();

        if ($zonaObjs->isEmpty()) {
            return response()->json([], 200);
        }

        $cicloObj = InvCiclo::find($ciclo);

        if (!$cicloObj) {
            return response()->json([
                'status' => 'NOK',
                'message' => 'Ciclo no encontrado',
                'code' => 404
            ], 404);
        }

        $emplazamientos = collect();

        foreach ($zonaObjs as $zonaObj) {
            $emplaCats = $cicloObj->zoneSubEmplazamientosWithCats($zonaObj)->pluck('idUbicacionN3')->toArray();



            $subEmplas = empty($emplaCats)
                ? $zonaObj->subemplazamientosNivel3()->get()
                : $zonaObj->subemplazamientosNivel3()->whereIn('idUbicacionN3', $emplaCats)->get();

            foreach ($subEmplas as $sub) {
                $sub->cycle_id = $ciclo;
                $emplazamientos->push($sub);
            }
        }



        return response()->json(EmplazamientoNivel3Resource::collection($emplazamientos), 200);
    }

    

    public function selectEmplazamientosN2(Request $request, int $ciclo, int $agenda_id)
{
    $zonaObjs = Emplazamiento::where('idAgenda', '=', $agenda_id)->get();

     $cicloObj = InvCiclo::find($ciclo);

        if (!$cicloObj) {
            return response()->json([
                'status' => 'NOK',
                'message' => 'Ciclo no encontrado',
                'code' => 404
            ], 404);
        }

        $emplazamientos = collect();

        foreach ($zonaObjs as $zonaObj) {
            $emplaCats = $cicloObj->zoneEmplazamientosWithCats($zonaObj)->pluck('idUbicacionN2')->toArray();



            $subEmplas = empty($emplaCats)
                ? $zonaObj->emplazamientos()->get()
                : $zonaObj->emplazamientos()->whereIn('idUbicacionN2', $emplaCats)->get();

            foreach ($subEmplas as $sub) {
                $sub->cycle_id = $ciclo;
                $emplazamientos->push($sub);
            }
        }


    return response()->json(EmplazamientoResource::collection($emplazamientos), 200);
}

  public function selectEmplazamientosN3(Request $request, int $ciclo, int $agenda_id)
{
    $zonaObjs = EmplazamientoN3::where('idAgenda', '=', $agenda_id)->get();

   if ($zonaObjs->isEmpty()) {
            return response()->json([], 200);
        }

        $cicloObj = InvCiclo::find($ciclo);

        if (!$cicloObj) {
            return response()->json([
                'status' => 'NOK',
                'message' => 'Ciclo no encontrado',
                'code' => 404
            ], 404);
        }

        $emplazamientos = collect();

        foreach ($zonaObjs as $zonaObj) {
            $emplaCats = $cicloObj->zoneSubEmplazamientosWithCats($zonaObj)->pluck('idUbicacionN3')->toArray();



            $subEmplas = empty($emplaCats)
                ? $zonaObj->subemplazamientosNivel3()->get()
                : $zonaObj->subemplazamientosNivel3()->whereIn('idUbicacionN3', $emplaCats)->get();

            foreach ($subEmplas as $sub) {
                $sub->cycle_id = $ciclo;
                $emplazamientos->push($sub);
            }
        }



        return response()->json(EmplazamientoNivel3Resource::collection($emplazamientos), 200);
}

  public function CycleCatsNivel1(Request $request, int $ciclo, int $agenda_id)
{
    $zonaObjs = EmplazamientoN1::where('idAgenda', '=', $agenda_id)->get();

    if ($zonaObjs->isEmpty()) {
        return response()->json([], 200);
    }

    $cicloObj = InvCiclo::find($ciclo);

    if (!$cicloObj) {
        return response()->json([
            'status' => 'NOK',
            'message' => 'Ciclo no encontrado',
            'code' => 404
        ], 404);
    }

    $emplazamientos = collect();

    foreach ($zonaObjs as $zonaObj) {
        $emplaCats = $cicloObj->EmplazamientosWithCatsN1($zonaObj)->pluck('idUbicacionN1')->toArray();

        $subEmplas = empty($emplaCats)
            ? $zonaObj->zoneEmplazamientosN1()->get()
            : $zonaObj->zoneEmplazamientosN1()->whereIn('idUbicacionN1', $emplaCats)->get();

        foreach ($subEmplas as $sub) {
            $sub->cycle_id = $ciclo;
            $emplazamientos->push($sub);
        }
    }

    $emplazamientos = $emplazamientos->unique('idUbicacionN1')->values();

    return response()->json(EmplazamientoNivel1Resource::collection($emplazamientos), 200);
}


    public function showAllEmplaByCycleCats(Request $request, int $ciclo)
    {

        $cicloObj = InvCiclo::find($ciclo);

        if (!$cicloObj) {
            return response()->json(['status' => 'NOK', 'message' => 'Ciclo no encontrado', 'code' => 404], 404);
        }


        if ($cicloObj->idTipoCiclo == 1) {
            $emplazamientos = $cicloObj->emplazamientos_with_cats_inv()->get();
        } else {
            $emplazamientos = $cicloObj->emplazamientos_with_cats()->get();
        }

        foreach ($emplazamientos as $emplazamiento) {
            $emplazamiento->cycle_id = $ciclo;
        }

        return response()->json(EmplazamientoResource::collection($emplazamientos), 200);
    }

     public function regiones()
    {
        $RegionesObj = Region::all();

        if ($RegionesObj->isEmpty()) {
            return response()->json(['status' => 'NOK', 'code' => 404], 404);
        }

        return response()->json($RegionesObj);
    }

     public function comunas(int $idRegion)
    {
        $ComunaObj = DB::table('comunas')->where('idRegion', $idRegion)->get();

        if ($ComunaObj->isEmpty()) {
            return response()->json(['status' => 'NOK', 'code' => 404], 404);
        }

        return response()->json($ComunaObj);
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
