<?php

namespace App\Http\Controllers\Api\V1\Comunes;

use App\Http\Controllers\Controller;
use App\Models\IndiceLista;
use App\Models\Inventario_bienes;
use App\Models\Inventario_marcas;
use App\Models\IndiceLista13;
use App\Models\Responsable;
use Illuminate\Http\Request;
use App\Models\Grupo;
use App\Models\Familia;
use App\Models\IndiceListaColores;
use App\Models\IndiceListaOperacional;
use App\Models\IndiceListaTipoTrabajo;
use App\Models\IndiceListaCargaTrabajo;
use App\Models\IndiceListaConservacion;
use App\Models\IndiceListaCondicionAmbiental;
use App\Models\IndiceListaMaterial;
use App\Models\IndiceListaForma;
use App\Models\Inv_ciclos_categorias;
use Illuminate\Support\Facades\DB;


class DatosActivosController extends Controller
{
    //
    public function marcas()
    {
        $collection = IndiceLista::all();
        return response()->json($collection, 200);
    }

    public function responsables()
    {
        $collection = Responsable::all();
        return response()->json($collection, 200);
    }

    public function estadosBienes()
    {
        $collection = IndiceLista13::all();

        return response()->json($collection, 200);
    }
    public function grupo($ciclo)
    {
        $idsGrupos = Inv_ciclos_categorias::where('idCiclo', $ciclo)->pluck('id_grupo')->toArray();

        $grupos = Grupo::whereIn('id_grupo', $idsGrupos)->get();

        return response()->json($grupos, 200);
    }

    public function familia($codigo_grupo)
    {

        $collection = Familia::where('codigo_grupo', $codigo_grupo)->get();

        return response()->json($collection, 200);
    }

    public function bienes_Marcas($id_familia)
    {
        $sql = "
            SELECT * FROM (
                SELECT * FROM inv_bienes_nuevos WHERE id_familia = :id_familia_bienes
                UNION ALL
                SELECT * FROM inv_marcas_nuevos WHERE id_familia = :id_familia_marcas
                UNION ALL
                SELECT * FROM indices_listas WHERE id_familia = :id_familia_indices
            ) AS resultado
        ";

        $bienesMarcas = DB::select($sql, [
            'id_familia_bienes' => $id_familia,
            'id_familia_marcas' => $id_familia,
            'id_familia_indices' => $id_familia,
        ]);

        return response()->json($bienesMarcas, 200);
    }

    public function indiceColores()
    {

        $collection = IndiceListaColores::all();

        return response()->json($collection, 200);
    }

    public function estadosOperacional()
    {

        $collection = IndiceListaOperacional::all();

        return response()->json($collection, 200);
    }
    public function tipoTrabajo()
    {

        $collection = IndiceListaTipoTrabajo::all();

        return response()->json($collection, 200);
    }
    public function cargaTrabajo()
    {

        $collection = IndiceListaCargaTrabajo::all();

        return response()->json($collection, 200);
    }

    public function estadoConservacion()
    {

        $collection = IndiceListaConservacion::all();

        return response()->json($collection, 200);
    }

    public function condicionAmbiental()
    {

        $collection = IndiceListaCondicionAmbiental::all();

        return response()->json($collection, 200);
    }
    public function material($id_familia)
    {

        $collection = IndiceListaMaterial::where('id_familia', $id_familia)->get();

        return response()->json($collection, 200);
    }

    public function forma($id_familia)
    {

        $collection = IndiceListaForma::where('id_familia', $id_familia)->get();

        return response()->json($collection, 200);
    }

    public function showBienes(Request $request)
    {
        $request->validate([
            'descripcion'       => 'required|string',
            'observacion'       => 'required|string',
            'idAtributo'        => 'required',
            'id_familia'        => 'required',
            'ciclo_inventario'  => 'required'
        ]);

        $sql  = "SELECT * FROM inv_bienes_nuevos WHERE idIndice = $request->id_familia AND idAtributo = $request->idAtributo";

        $indice = DB::selectOne($sql);

        $maxListaIndicelista = Indicelista::where('idAtributo', $request->idAtributo)
            ->where('idIndice', $request->id_familia)
            ->max('idLista');

        $maxListaMarcasNuevos = Inventario_bienes::where('idAtributo', $request->idAtributo)
            ->where('id_familia', $request->id_familia)
            ->max('idLista');

        if ($maxListaIndicelista === null && $maxListaMarcasNuevos === null) {
            $newIdLista = 1;
        } else {
            $newIdLista = max($maxListaIndicelista, $maxListaMarcasNuevos) + 1;
        }

        $bienes = new Inventario_bienes();
        $bienes->idLista     = $newIdLista;
        $bienes->idIndice    = $request->id_familia;
        $bienes->descripcion = $request->descripcion;
        $bienes->observacion = $request->observacion;
        $bienes->idAtributo  = $request->idAtributo;
        $bienes->id_familia  = $request->id_familia;
        $bienes->ciclo_inventario = $request->ciclo_inventario;
        $bienes->save();

        return response()->json([
            'status'    => 'OK',
            'message'   => 'Creado exitosamente',
            'data'      => [
                'idLista'     => $bienes->idLista,
                'idIndice'    => $bienes->idIndice,
                'descripcion' => $bienes->descripcion,
                'observacion' => $bienes->observacion,
                'idAtributo'  => $bienes->idAtributo,
                'id_familia'  => $bienes->id_familia,
                'ciclo_inventario' => $bienes->ciclo_inventario,
            ]
        ]);
    }

    public function showMarcas(Request $request)
    {
        $request->validate([
            'descripcion'       => 'required|string',
            'observacion'       => 'required|string',
            'idAtributo'        => 'required',
            'id_familia'        => 'required',
            'ciclo_inventario'  => 'required'
        ]);

        $sql  = "SELECT * FROM inv_marcas_nuevos WHERE idIndice = $request->id_familia AND idAtributo = $request->idAtributo";

        $indice = DB::selectOne($sql);

        $maxListaIndicelista = Indicelista::where('idAtributo', $request->idAtributo)
            ->where('idIndice', $request->id_familia)
            ->max('idLista');

        $maxListaMarcasNuevos = Inventario_marcas::where('idAtributo', $request->idAtributo)
            ->where('id_familia', $request->id_familia)
            ->max('idLista');

        if ($maxListaIndicelista === null && $maxListaMarcasNuevos === null) {
            $newIdLista = 1;
        } else {
            $newIdLista = max($maxListaIndicelista, $maxListaMarcasNuevos) + 1;
        }


        $marcas = new Inventario_marcas();
        $marcas->idLista     = $newIdLista;
        $marcas->idIndice    = $request->id_familia;
        $marcas->descripcion = $request->descripcion;
        $marcas->observacion = $request->observacion;
        $marcas->idAtributo  = $request->idAtributo;
        $marcas->id_familia  = $request->id_familia;
        $marcas->ciclo_inventario = $request->ciclo_inventario;
        $marcas->save();

        return response()->json([
            'status'    => 'OK',
            'message'   => 'Creado exitosamente',
            'data'      => [
                'idLista'     => $marcas->idLista,
                'idIndice'    => $marcas->idIndice,
                'descripcion' => $marcas->descripcion,
                'observacion' => $marcas->observacion,
                'idAtributo'  => $marcas->idAtributo,
                'id_familia'  => $marcas->id_familia,
                'ciclo_inventario' => $marcas->ciclo_inventario,
            ]
        ]);
    }
}
