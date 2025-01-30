<?php

namespace App\Http\Controllers\Api\V1\Comunes;

use App\Http\Controllers\Controller;
use App\Models\IndiceLista;
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
        $collection = IndiceLista::where('id_familia', $id_familia)->get();
    
        return response()->json($collection, 200);
    }

    public function indiceColores(){

        $collection = IndiceListaColores::all();
    
        return response()->json($collection, 200);
    }

    public function estadosOperacional(){

        $collection = IndiceListaOperacional::all();
    
        return response()->json($collection, 200);
    }
    public function tipoTrabajo(){

        $collection = IndiceListaTipoTrabajo::all();
    
        return response()->json($collection, 200);
    }
    public function cargaTrabajo(){

        $collection = IndiceListaCargaTrabajo::all();
    
        return response()->json($collection, 200);
    }

    public function estadoConservacion(){

        $collection = IndiceListaConservacion::all();
    
        return response()->json($collection, 200);
    }

    public function condicionAmbiental(){

        $collection = IndiceListaCondicionAmbiental::all();
    
        return response()->json($collection, 200);
    }
    public function material($id_familia){

        $collection = IndiceListaMaterial::where('id_familia', $id_familia)->get();

        return response()->json($collection, 200);
    }

    public function forma($id_familia){

        $collection = IndiceListaForma::where('id_familia', $id_familia)->get();

        return response()->json($collection, 200);
    }

    public function showBienes(Request $request){
        $request->validate([
            'descripcion'       => 'required|string',
            'observacion'       => 'required|string',
            'idAtributo'        => 'required',
            'id_familia'        => 'required',
            'ciclo_inventario'  => 'required'      
        ]);

        $bienes = new IndiceLista();
        $bienes->idLista = 12;
        $bienes->idIndice = 12;
        $bienes->descripcion = $request->descripcion;
        $bienes->observacion = $request->observacion;
        $bienes->idAtributo = $request->idAtributo;
        $bienes->id_familia = $request->id_familia;
        $bienes->ciclo_inventario = $request->ciclo_inventario;
        $bienes->save();

        return response()->json([
            'status'    => 'OK',
            'message'   => 'Creado exitosamente'
        ]);
    }
}
