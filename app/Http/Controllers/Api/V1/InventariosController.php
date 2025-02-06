<?php

namespace App\Http\Controllers\Api\V1;
use Illuminate\Http\Request;
use App\Models\Inventario;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;


class InventariosController extends Controller
{
      
    public function createinventario(Request $request){

        $request->validate([
            'id_grupo'              => 'required|string',
            'id_familia'            => 'required|string',
            'id_ciclo'              => 'required'
        ]);

        $inventario = new Inventario();
        $inventario->id_grupo            = $request->id_grupo;
        $inventario->id_familia          = $request->id_familia;
        $inventario->descripcion_bien    = $request->descripcion_bien;
        $inventario->descripcion_marca   = $request->descripcion_marca ? $request->descripcion_marca : '';
        $inventario->idForma             = is_numeric($request->idForma) ? intval($request->idForma) : 0;
        $inventario->idMaterial          = is_numeric($request->idMaterial) ? intval($request->idMaterial) : 0;
        $inventario->etiqueta            = $request->etiqueta;
        $inventario->modelo              = $request->modelo? $request->modelo : '';
        $inventario->serie               = $request->serie? $request->serie : '';
        $inventario->capacidad           = is_numeric($request->capacidad) ? intval($request->capacidad) : 0;
        $inventario->estado              = is_numeric($request->estado) ? intval($request->estado) : 0;
        $inventario->color               = is_numeric($request->color) ? intval($request->color) : 0;
        $inventario->tipo_trabajo        = is_numeric($request->tipo_trabajo) ? intval($request->tipo_trabajo) : 0;
        $inventario->carga_trabajo       = is_numeric($request->carga_trabajo) ? intval($request->carga_trabajo) : 0;
        $inventario->estado_operacional  = is_numeric($request->estado_operacional) ? intval($request->estado_operacional) : 0;
        $inventario->estado_conservacion = is_numeric($request->estado_conservacion) ? intval($request->estado_conservacion) : 0;
        $inventario->condicion_ambiental = is_numeric($request->condicion_ambiental) ? intval($request->condicion_ambiental) : 0;
        $inventario->cantidad_img        = $request->cantidad_img;
        $inventario->id_ciclo            = $request->id_ciclo;
        $inventario->save();

        return response()->json($inventario, 201);
    }


    public function configuracion($id_grupo, $modelo, $serie, $capacidad, $marcas){
        $sql = "SELECT 
                    MAX(CASE WHEN id_atributo = $modelo THEN id_validacion END) AS conf_modelo,
                    MAX(CASE WHEN id_atributo = $serie THEN id_validacion END) AS conf_serie,
                    MAX(CASE WHEN id_atributo = $capacidad THEN id_validacion END) AS conf_capacidad,
                    MAX(CASE WHEN id_atributo = $marcas THEN id_validacion END) AS conf_marcas
                FROM inv_atributos 
                WHERE id_atributo IN ($modelo, $serie, $capacidad, $marcas) 
                AND id_grupo = $id_grupo";
        $validacion = DB::select($sql);

        return response()->json($validacion, 200);

    }
}
