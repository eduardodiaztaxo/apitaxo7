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
use App\Models\IndiceListaEstado;
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

     public function estadosInventario()
    {
        $collection = IndiceListaEstado::all();
        return response()->json($collection, 200);
    }


        public function grupo($ciclo)
        {
            $idsGrupos = DB::select("
                SELECT 
                    dp_grupos.descripcion_grupo,
                    dp_familias.descripcion_familia,
                    dp_familias.id_grupo,
                    dp_familias.id_familia
                FROM dp_grupos
                INNER JOIN dp_familias 
                    ON dp_grupos.id_grupo = dp_familias.id_grupo
                LEFT JOIN inv_ciclos_categorias 
                    ON inv_ciclos_categorias.id_familia = dp_familias.id_familia
                AND inv_ciclos_categorias.id_grupo = dp_familias.id_grupo
                AND inv_ciclos_categorias.idCiclo = ?
                WHERE IFNULL(inv_ciclos_categorias.id_familia, '0') <> '0'
                ORDER BY dp_grupos.descripcion_grupo, dp_familias.descripcion_familia
            ", [$ciclo]);

            $ids = collect($idsGrupos)->pluck('id_grupo')->unique()->values()->toArray();

            $grupos = Grupo::whereIn('id_grupo', $ids)->get();

            return response()->json($grupos, 200);
        }


    public function familia($codigo_grupo, $ciclo)
    {
          $idsFamilia = DB::select("
                SELECT 
                    dp_grupos.descripcion_grupo,
                    dp_familias.descripcion_familia,
                    dp_familias.id_grupo,
                    dp_familias.id_familia
                FROM dp_grupos
                INNER JOIN dp_familias 
                    ON dp_grupos.id_grupo = dp_familias.id_grupo
                LEFT JOIN inv_ciclos_categorias 
                    ON inv_ciclos_categorias.id_familia = dp_familias.id_familia
                AND inv_ciclos_categorias.id_grupo = dp_familias.id_grupo
                AND inv_ciclos_categorias.idCiclo = ?
                AND inv_ciclos_categorias.id_grupo = ?
                WHERE IFNULL(inv_ciclos_categorias.id_familia, '0') <> '0'
                ORDER BY dp_grupos.descripcion_grupo, dp_familias.descripcion_familia
            ", [$ciclo, $codigo_grupo]);

            $ids = collect($idsFamilia)->pluck('id_familia')->unique()->values()->toArray();

        $collection = Familia::where('id_grupo', $codigo_grupo)->whereIn('id_familia', $ids)->get();

        return response()->json($collection, 200);
    }
 public function bienesGrupoFamilia($idCiclo)
{
    // Paso 1: Obtener familias con estado ≠ 0
    $familias = DB::select("
        SELECT 
            dp_grupos.descripcion_grupo,
            dp_familias.descripcion_familia,
            dp_familias.id_grupo,
            dp_familias.id_familia
        FROM dp_grupos
        INNER JOIN dp_familias 
            ON dp_grupos.id_grupo = dp_familias.id_grupo
        LEFT JOIN inv_ciclos_categorias 
            ON inv_ciclos_categorias.id_familia = dp_familias.id_familia
           AND inv_ciclos_categorias.id_grupo = dp_familias.id_grupo
           AND inv_ciclos_categorias.idCiclo = ?
        WHERE IFNULL(inv_ciclos_categorias.id_familia, '0') <> '0'
        ORDER BY dp_grupos.descripcion_grupo, dp_familias.descripcion_familia
    ", [$idCiclo]);

    // Convertimos a colección para facilitar manipulación
    $familias = collect($familias);

    // Paso 2: Extraer IDs de familia
    $idsFamilia = $familias->pluck('id_familia')->all();

    if (empty($idsFamilia)) {
        return response()->json([], 200);
    }

    // Paso 3: Consulta bienes
    $in = implode(',', $idsFamilia);
    $bienesRaw = DB::select("
       SELECT * FROM (
    SELECT 
        idLista,
        idAtributo,
        idIndice,
        idProyecto,
        descripcion,
        observacion,
        listaRapida,
        creadoPor,
        modificadoPor,
        fechaCreacion,
        fechaModificacion,
        estado,
        foto,
        id_familia,
        ciclo_inventario
    FROM inv_bienes_nuevos
    WHERE idAtributo = 1 AND id_familia IN ($in)

    UNION ALL

    SELECT 
        idLista,
        idAtributo,
        idIndice,
        idProyecto,
        descripcion,
        observacion,
        listaRapida,
        creadoPor,
        modificadoPor,
        fechaCreacion,
        fechaModificacion,
        estado,
        foto,
        id_familia,
        ciclo_inventario
    FROM indices_listas
    WHERE idAtributo = 1 AND id_familia IN ($in)
) AS resultado;
 ");

    // Paso 4: Enriquecer los bienes con info de grupo y familia
    $bienes = collect($bienesRaw)
        ->map(function ($bien) use ($familias) {
            $familia = $familias->firstWhere('id_familia', $bien->id_familia);
            if ($familia) {
                $bien->descripcion_familia = $familia->descripcion_familia;
                $bien->descripcion_grupo = $familia->descripcion_grupo;
                $bien->id_grupo = $familia->id_grupo;
            }
            return $bien;
        });

    return response()->json($bienes->values(), 200);
}

    
    
    public function buscarGrupoFamilia($id_familia)
    {
        $sql = "
          SELECT descripcion_familia, id_grupo FROM dp_familias WHERE id_familia = $id_familia
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
    public function bienes_Marcas($id_familia, $ciclo)
    {
        $marcas = DB::table('inv_marcas_nuevos')
                    ->where('id_familia', $id_familia)
                    ->where('ciclo_inventario', $ciclo)
                    ->get();
    
                    
        $indices = DB::table('indices_listas')
                    ->where('id_familia', $id_familia)
                    ->where('idAtributo', 2)
                    ->get();
    
        // Unir ambas colecciones
        $resultado = $marcas->concat($indices);
    
        return response()->json($resultado, 200);
    }

public function indiceColores()
{
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

     public function material()
    {
        $collection = IndiceListaMaterial::all();
        return response()->json($collection, 200);
    }

    /**
     * Se busca el forma por familia.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\IndiceListaForma  
     * @return \Illuminate\Http\Response
     */


    public function forma()
    {
        $collection = IndiceListaForma::all();
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