<?php

namespace App\Http\Controllers\Api\V1\Comunes;

use App\Http\Controllers\Controller;
use App\Models\IndiceLista;
use App\Models\Responsable;
use Illuminate\Http\Request;

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
}
