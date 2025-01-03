<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\CategoriaN1;
use App\Models\CategoriaN2;
use App\Models\IndiceLista;


class InventariosController extends Controller
{
       public function grupo()
    {
       
    
        $collection = CategoriaN1::all();
    
        return response()->json($collection, 200);
    }
    public function familia($codigoCategoria)
    {
        $collection = CategoriaN2::where('codigoCategoria', 'LIKE', $codigoCategoria . '%')->get();
    
        return response()->json($collection, 200);
    }

    public function bienes_Marcas()
    {
        $collection = IndiceLista::all();
    
        return response()->json($collection, 200);
    }

}
