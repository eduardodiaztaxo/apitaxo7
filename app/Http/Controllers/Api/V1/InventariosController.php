<?php

namespace App\Http\Controllers\Api\V1;
use Illuminate\Http\Request;
use App\Models\Inventario;
use App\Http\Controllers\Controller;



class InventariosController extends Controller
{
      
    public function showinventario(Request $request){

        $request->validate([
            'id_grupo'              => 'required|string',
            'id_familia'            => 'required|string',
            'descripcion_bien'       => 'required',
            'descripcion_marca'      => 'required',
            'idForma'               => 'required',
            'idMaterial'            => 'required',     
            'etiqueta'              => 'required',
            'modelo'                => 'required',
            'serie'                 => 'required',
            'capacidad'             => 'required',
            'estado'                => 'required',
            'color'                 => 'required',
            'tipo_trabajo'          => 'required',
            'carga_trabajo'         => 'required',
            'estado_operacional'    => 'required',
            'estado_conservacion'   => 'required',
            'condicion_ambiental'   => 'required',
            'cantidad_img'          => 'required',
            'id_ciclo'              => 'required'
        ]);

        $inventario = new Inventario();
        $inventario->id_grupo            = $request->id_grupo;
        $inventario->id_familia          = $request->id_familia;
        $inventario->descripcion_bien    = $request->descripcion_bien;
        $inventario->descripcion_marca   = $request->descripcion_marca;
        $inventario->idForma             = $request->idForma;
        $inventario->idMaterial          = $request->idMaterial;
        $inventario->etiqueta            = $request->etiqueta;
        $inventario->modelo              = $request->modelo;
        $inventario->serie               = $request->serie;
        $inventario->capacidad           = $request->capacidad;
        $inventario->estado              = $request->estado;
        $inventario->color               = $request->color;
        $inventario->tipo_trabajo        = $request->tipo_trabajo;
        $inventario->carga_trabajo       = $request->carga_trabajo;
        $inventario->estado_operacional  = $request->estado_operacional;
        $inventario->estado_conservacion = $request->estado_conservacion;
        $inventario->condicion_ambiental = $request->condicion_ambiental;
        $inventario->cantidad_img        = $request->cantidad_img;
        $inventario->id_ciclo            = $request->id_ciclo;
        $inventario->save();

        return response()->json([
            'message' => 'Inventario creado con éxito'
        ], 200);
    }
}
