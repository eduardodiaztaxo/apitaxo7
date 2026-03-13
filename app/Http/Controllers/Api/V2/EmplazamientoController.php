<?php

namespace App\Http\Controllers\Api\V2;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class EmplazamientoController extends Controller
{
    public function show(int $ciclo, int $nivel, int $emplazamiento)
    {
        $modelName = "App\Models\EmplazamientoN{$nivel}";
        $resourceName = "App\Http\Resources\V2\EmplazamientoNivel{$nivel}Resource";

        if (!class_exists($modelName) || !class_exists($resourceName)) {
            return response()->json(['status' => 'error', 'code' => 400, 'message' => 'Invalid level'], 400);
        }

        $emplaObj = $modelName::find($emplazamiento);

        if (!$emplaObj) {
            return response()->json(['status' => 'error', 'code' => 404], 404);
        }

        $emplaObj->requirePunto = 1;
        $emplaObj->cycle_id = $ciclo;

        $resource = $resourceName::make($emplaObj);

        return response()->json($resource);
    }
}
