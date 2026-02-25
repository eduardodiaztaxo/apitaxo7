<?php

namespace App\Http\Controllers\Api\V1\User;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Exception;

class UserInteractionController extends Controller
{

    public function saveInteraction(Request $request)
    {
        try {
            $data = $request->all();
            
            DB::table('user_interactions')->insert([
                'user_id'          => $data['id_user'] ?? ($request->user() ? $request->user()->id : null),
                'interaction_type' => $data['tipo_interaccion'] ?? 'unknown',
                'etiqueta'         => $data['etiqueta'] ?? null,
                'id_activo'        => $data['id_activo'] ?? null,
                'id_ciclo'         => $data['id_ciclo'] ?? null,
                'client_at'        => isset($data['fecha_cliente']) ? Carbon::parse($data['fecha_cliente']) : now(),
                'created_at'       => now(),
                'updated_at'       => now(),
            ]);

            return response()->json(['status' => 'success'], 201);
            
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error', 
                'message' => 'Error al loguear interaccion',
                'debug' => $e->getMessage() 
            ], 500);
        }
    }
}
