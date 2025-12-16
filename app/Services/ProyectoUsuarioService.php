<?php

namespace App\Services;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Servicio para obtener el proyecto del usuario autenticado
 * 
 * Uso:
 * $idProyecto = ProyectoUsuarioService::getIdProyecto();
 */
class ProyectoUsuarioService
{
    /**
     * Obtener id_proyecto del usuario autenticado
     *
     * @return int|null
     */
    public static function getIdProyecto()
    {
        $usuario = Auth::user()->name;
        
        return DB::table('sec_user_proyectos')
                 ->where('login', $usuario)
                 ->value('idProyecto');
    }

    /**
     * Obtener id_proyecto del usuario autenticado con validación
     * Lanza excepción si no se encuentra
     *
     * @return int
     * @throws \Exception
     */
    public static function getIdProyectoOrFail()
    {
        $idProyecto = self::getIdProyecto();
        
        if (!$idProyecto) {
            throw new \Exception('No se encontró proyecto para el usuario');
        }
        
        return $idProyecto;
    }
}
