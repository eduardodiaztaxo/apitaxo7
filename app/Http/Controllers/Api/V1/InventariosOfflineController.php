<?php

namespace App\Http\Controllers\Api\V1;
use Illuminate\Http\Request;
use App\Models\Inventario;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use App\Models\Inv_imagenes;
use App\Models\Familia;
use App\Models\Responsable;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Intervention\Image\Facades\Image;
use Illuminate\Support\Facades\Storage;


class InventariosOfflineController extends Controller
{


    public function __construct()
    {} 

public function inventarioPorCicloOfflineInventario($ciclo){
    $collection = DB::table('inv_inventario')
                    ->where('id_ciclo', $ciclo)
                    ->get();
                    
        return response()->json($collection, 200);
}

 public function responsables()
{
    $collection = Responsable::paginate(100); 
    return response()->json($collection, 200);
}

    
public function familia(int $ciclo, array $codigo_grupos)
{

    if (count($codigo_grupos) === 1 && is_string($codigo_grupos[0])) {
        $codigo_grupos = explode(',', $codigo_grupos[0]);
        $codigo_grupos = array_map('intval', $codigo_grupos);
    }
    $resultados = [];

    $placeholders = implode(',', array_fill(0, count($codigo_grupos), '?'));

    $sql = "
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
            AND inv_ciclos_categorias.id_grupo IN ($placeholders)
        WHERE IFNULL(inv_ciclos_categorias.id_familia, '0') <> '0'
        ORDER BY dp_grupos.descripcion_grupo, dp_familias.descripcion_familia
    ";

    $params = array_merge([$ciclo], $codigo_grupos);

    $idsFamilia = DB::select($sql, $params);
  
    foreach ($codigo_grupos as $codigo_grupo) {
        $ids = collect($idsFamilia)
                    ->where('id_grupo', $codigo_grupo)
                    ->pluck('id_familia')
                    ->unique()
                    ->values()
                    ->toArray();

        $collection = Familia::where('id_grupo', $codigo_grupo)
                            ->whereIn('id_familia', $ids)
                            ->get();

        $resultados = array_merge($resultados, $collection->toArray());
    }

    return response()->json($resultados, 200);
}



    public function MarcasPorCicloOfflineInventario($ciclo)
    {
        $marcas = DB::table('inv_marcas_nuevos')
                    ->where('ciclo_inventario', $ciclo)
                    ->get();

        $indices = DB::table('indices_listas')
                    ->where('idAtributo', 2)
                    ->get();

        $resultado = $marcas->concat($indices);
        return response()->json($resultado, 200);
    }

   public function configuracionOffline(array $codigo_grupos)
    {
        if (count($codigo_grupos) === 1 && is_string($codigo_grupos[0])) {
            $codigo_grupos = explode(',', $codigo_grupos[0]);
            $codigo_grupos = array_map('intval', $codigo_grupos);
        }
        $resultados = [];

        $placeholders = implode(',', array_fill(0, count($codigo_grupos), '?'));

        foreach ($codigo_grupos as $id_grupo) {
            $sql = "SELECT 
                        ? AS id_grupo,
                        COALESCE(MAX(CASE WHEN id_atributo = 2 THEN id_validacion END), 0) AS conf_marca,
                        COALESCE(MAX(CASE WHEN id_atributo = 3 THEN id_validacion END), 0) AS conf_modelo,
                        COALESCE(MAX(CASE WHEN id_atributo = 3 THEN id_tipo_dato END), 0) AS tipo_dato_mod,
                        COALESCE(MAX(CASE WHEN id_atributo = 3 THEN valor_minimo END), 0) AS lench_Min_mod,
                        COALESCE(MAX(CASE WHEN id_atributo = 3 THEN valor_maximo END), 0) AS lench_Max_mod,
                        COALESCE(MAX(CASE WHEN id_atributo = 4 THEN id_validacion END), 0) AS conf_capacidad,
                        COALESCE(MAX(CASE WHEN id_atributo = 4 THEN id_tipo_dato END), 0) AS tipo_dato_cap,
                        COALESCE(MAX(CASE WHEN id_atributo = 4 THEN valor_minimo END), 0) AS lench_Min_cap,
                        COALESCE(MAX(CASE WHEN id_atributo = 4 THEN valor_maximo END), 0) AS lench_Max_cap,
                        COALESCE(MAX(CASE WHEN id_atributo = 6 THEN id_validacion END), 0) AS conf_material,
                        COALESCE(MAX(CASE WHEN id_atributo = 7 THEN id_validacion END), 0) AS conf_forma,
                        COALESCE(MAX(CASE WHEN id_atributo = 8 THEN id_validacion END), 0) AS conf_estado,
                        COALESCE(MAX(CASE WHEN id_atributo = 9 THEN id_validacion END), 0) AS conf_estado_operacional,
                        COALESCE(MAX(CASE WHEN id_atributo = 10 THEN id_validacion END), 0) AS conf_serie,
                        COALESCE(MAX(CASE WHEN id_atributo = 10 THEN id_tipo_dato END), 0) AS tipo_dato_serie,
                        COALESCE(MAX(CASE WHEN id_atributo = 10 THEN valor_minimo END), 0) AS lench_Min_serie,
                        COALESCE(MAX(CASE WHEN id_atributo = 10 THEN valor_maximo END), 0) AS lench_Max_serie,
                        COALESCE(MAX(CASE WHEN id_atributo = 14 THEN id_validacion END), 0) AS conf_color,
                        COALESCE(MAX(CASE WHEN id_atributo = 18 THEN id_validacion END), 0) AS conf_estado_conservacion,
                        COALESCE(MAX(CASE WHEN id_atributo = 19 THEN id_validacion END), 0) AS conf_tipo_trabajo,
                        COALESCE(MAX(CASE WHEN id_atributo = 20 THEN id_validacion END), 0) AS conf_carga_trabajo,
                        COALESCE(MAX(CASE WHEN id_atributo = 21 THEN id_validacion END), 0) AS conf_condicion_ambiental,
                        COALESCE(MAX(CASE WHEN id_atributo = 22 THEN valor_minimo END), 0) AS lench_Min_etiqueta,
                        COALESCE(MAX(CASE WHEN id_atributo = 22 THEN valor_maximo END), 0) AS lench_Max_etiqueta,
                        COALESCE(MAX(CASE WHEN id_atributo = 22 THEN tipo_etiqueta END), '') AS tipo_etiqueta,
                        COALESCE(MAX(CASE WHEN id_atributo = 23 THEN id_validacion END), 0) AS conf_latitud,
                        COALESCE(MAX(CASE WHEN id_atributo = 24 THEN id_validacion END), 0) AS conf_longitud
                    FROM inv_atributos 
                    WHERE id_grupo = ?";

            $resultado = DB::select($sql, [$id_grupo, $id_grupo]);

            if (!empty($resultado)) {
                $resultados[] = $resultado[0]; 
            }
        }

        return response()->json($resultados, 200);
    }


}    