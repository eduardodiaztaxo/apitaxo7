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

    public function estadosInventario($id_grupo){

        $collection = IndiceLista13::select('indices_listas_13.*', 
        'inv_atributos.id_validacion AS configuracion_estado'
    )
        ->join('inv_atributos', 'indices_listas_13.id_atributo', '=', 'inv_atributos.id_atributo')
        ->where('inv_atributos.id_grupo', $id_grupo)
        ->get();

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
        
        $collection = Familia::where('id_grupo', $codigo_grupo)->get();

        return response()->json($collection, 200);
    }
    public function bienesGrupoFamilia($idCiclo)
    {
       
        $idsGrupos = Inv_ciclos_categorias::where('idCiclo', $idCiclo)->pluck('id_grupo')->toArray();
    
      
        $familias = Familia::select(
                'dp_familias.id_familia',
                'dp_familias.descripcion_familia',
                'dp_familias.id_grupo',
                'dp_grupos.descripcion_grupo'
            )
            ->leftJoin('dp_grupos', 'dp_familias.id_grupo', '=', 'dp_grupos.id_grupo')
            ->whereIn('dp_familias.id_grupo', $idsGrupos)
            ->get();
    
  
        $idsfamilia = $familias->pluck('id_familia')->toArray();
    
        if (empty($idsfamilia)) {
            return response()->json([], 200);
        }
    
  
        $sql = "
            SELECT * FROM (
                SELECT * FROM inv_bienes_nuevos WHERE idAtributo = 1 AND id_familia IN (" . implode(',', $idsfamilia) . ")
                UNION ALL
                SELECT * FROM indices_listas WHERE idAtributo = 1 AND id_familia IN (" . implode(',', $idsfamilia) . ")
            ) AS resultado
        ";
    
        $bienes = DB::select($sql);
    
    
        $bienes = collect($bienes)
            ->filter(function ($bien) use ($familias) {
                return $familias->where('id_familia', $bien->id_familia)->isNotEmpty();
            })
            ->map(function ($bien) use ($familias) {
                $familia = $familias->where('id_familia', $bien->id_familia)->first();
                $bien->descripcion_familia = $familia->descripcion_familia;
                $bien->id_grupo = $familia->id_grupo;
                $bien->descripcion_grupo = $familia->descripcion_grupo;
                return $bien;
            });
    
        return response()->json($bienes->values(), 200); 
    }
    
    
    public function buscarGrupoFamilia($id_familia)
    {
        $sql = "
          SELECT descripcion_familia, id_grupo  FROM dp_familias WHERE id_familia = $id_familia
        ";
    
        $result = DB::select($sql);
    
        $id_grupo = $result[0]->id_grupo;
    
        $sql = "
          SELECT * FROM dp_grupos WHERE id_grupo = $id_grupo
        ";
    
        $grupo = DB::select($sql);
    
        return response()->json([
            'grupo' => $grupo[0]
        ], 200);
    }
    public function bienes_Marcas($id_familia)
    {
        $marcas = DB::table('inv_marcas_nuevos')
                    ->where('id_familia', $id_familia)
                    ->get();
    
        $indices = DB::table('indices_listas')
                    ->where('id_familia', $id_familia)
                    ->where('idAtributo', 2)
                    ->get();
    
        // Unir ambas colecciones
        $resultado = $marcas->concat($indices);
    
        return response()->json($resultado, 200);
    }
    
public function indiceColores($id_grupo)
{
    $collection = IndiceListaColores::select(
            'ind_list_colores.*', 
            'inv_atributos.id_validacion AS configuracion_color'
        )
        ->join('inv_atributos', 'ind_list_colores.id_atributo', '=', 'inv_atributos.id_atributo')
        ->where('inv_atributos.id_grupo', $id_grupo)
        ->get();
    
    return response()->json($collection, 200);
}

    public function estadosOperacional($id_grupo){

        $collection = IndiceListaOperacional::select(
            'ind_list_estados_operacionales.*', 
            'inv_atributos.id_validacion AS configuracion_op'
        )
        ->join('inv_atributos', 'ind_list_estados_operacionales.id_atributo', '=', 'inv_atributos.id_atributo')
        ->where('inv_atributos.id_grupo', $id_grupo)
        ->get();
    
        return response()->json($collection, 200);
    }
    public function tipoTrabajo($id_grupo){

        $collection = IndiceListaTipoTrabajo::select(
            'ind_list_tipo_trabajo.*', 
            'inv_atributos.id_validacion AS configuracion_tipo'
        )
        ->join('inv_atributos', 'ind_list_tipo_trabajo.id_atributo', '=', 'inv_atributos.id_atributo')
        ->where('inv_atributos.id_grupo', $id_grupo)
        ->get();
    
        return response()->json($collection, 200);
    }
    public function cargaTrabajo($id_grupo){

        $collection = IndiceListaCargaTrabajo::select(
            'ind_list_carga_trabajo.*', 
            'inv_atributos.id_validacion AS configuracion_carga'
        )
        ->join('inv_atributos', 'ind_list_carga_trabajo.id_atributo', '=', 'inv_atributos.id_atributo')
        ->where('inv_atributos.id_grupo', $id_grupo)
        ->get();
    
        return response()->json($collection, 200);
    }

    public function estadoConservacion($id_grupo){

        $collection = IndiceListaConservacion::select(
            'ind_list_estados_conservacion.*', 
            'inv_atributos.id_validacion AS configuracion_cons'
        )
        ->join('inv_atributos', 'ind_list_estados_conservacion.id_atributo', '=', 'inv_atributos.id_atributo')
        ->where('inv_atributos.id_grupo', $id_grupo)
        ->get();
    
        return response()->json($collection, 200);
    }

    public function condicionAmbiental($id_grupo){

        $collection = IndiceListaCondicionAmbiental::select(
            'ind_list_condicion_Ambiental.*', 
            'inv_atributos.id_validacion AS configuracion_amb'
        )
        ->join('inv_atributos', 'ind_list_condicion_Ambiental.id_atributo', '=', 'inv_atributos.id_atributo')
        ->where('inv_atributos.id_grupo', $id_grupo)
        ->get();
    
    
        return response()->json($collection, 200);
    }
    public function material($id_familia, $id_grupo)
{
    $collection = IndiceListaMaterial::select(
            'ind_list_materiales_por_familia.*', 
            'inv_atributos.id_validacion AS configuracion_mat'
        )
        ->from('ind_list_materiales_por_familia')
        ->join('inv_atributos', 'ind_list_materiales_por_familia.id_atributo', '=', 'inv_atributos.id_atributo')
        ->where('ind_list_materiales_por_familia.id_familia', $id_familia)
        ->where('inv_atributos.id_grupo', $id_grupo)
        ->get();
    
    return response()->json($collection, 200);
}
    /**
     * Se busca el forma por familia.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\IndiceListaForma  
     * @return \Illuminate\Http\Response
     */


    public function forma($id_familia, $id_grupo){

        $collection = IndiceListaForma::select(
            'ind_list_formas_por_familia.*', 
            'inv_atributos.id_validacion AS configuracion_forma'
        )
        ->from('ind_list_formas_por_familia')
        ->join('inv_atributos', 'ind_list_formas_por_familia.id_atributo', '=', 'inv_atributos.id_atributo')
        ->where('ind_list_formas_por_familia.id_familia', $id_familia)
        ->where('inv_atributos.id_grupo', $id_grupo)
        ->get();

        return response()->json($collection, 200);
    }

    /**
     * create bienes nuevos.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Inventario_bienes  
     * @param \Illuminate\Http\Indicelista se busca maximo idLista
     * @return \Illuminate\Http\Response
     */

    public function createBienes(Request $request){
        $request->validate([
            'descripcion'       => 'required|string',
            'observacion'       => 'required|string',
            'idAtributo'        => 'required|exists:indices_listas,idAtributo',
            'id_familia'        => 'required|exists:dp_familias,id_familia',
            'ciclo_inventario'  => 'required|exists:inv_ciclos,idCiclo'     
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
    
        return response()->json($bienes, 201);
    }

     /**
     * create marcas nuevas.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Inventario_marcas  
     * @param \Illuminate\Http\Indicelista se busca maximo idLista
     * @return \Illuminate\Http\Response
     */
    public function createMarcas(Request $request){
        $request->validate([
            'descripcion'       => 'required|string',
            'observacion'       => 'required|string',
            'idAtributo'        => 'required|exists:indices_listas,idAtributo',
            'id_familia'        => 'required|exists:dp_familias,id_familia',
            'ciclo_inventario'  => 'required|exists:inv_ciclos,idCiclo'       
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
    
        return response()->json($marcas, 201);
    }
}