<?php

namespace App\Services;

use App\Models\CrudActivo;
use App\Models\Inventario;
use App\Models\InvCiclo;
use Illuminate\Support\Facades\DB;

class ActivoFinderService
{
    const TIPO_INVENTARIO = 1;
    const TIPO_AUDITORIA = 2;

    /**
     * Buscar activo según tipo de ciclo
     * - Tipo 1: inv_inventario
     * - Tipo 2: crud_activos (etiqueta o codigo_cliente)
     *
     * @param  string  $identificador
     * @param  int  $cicloId
     * @return \App\Models\CrudActivo|\App\Models\Inventario|null
     */
    public static function findByEtiquetaAndCiclo($identificador, $cicloId)
    {
        // Si cicloId es 0 o null, es scan de bienes -> ir a crud_activos 
        if (!$cicloId) {
            return CrudActivo::where('etiqueta', '=', $identificador)
                             ->orWhere('codigo_cliente', '=', $identificador)
                             ->first();
        }

        $ciclo = InvCiclo::find($cicloId);

        if (!$ciclo) {
            return null;
        }

        // Inventario
        if ($ciclo->idTipoCiclo == self::TIPO_INVENTARIO) {
            $query = Inventario::where('etiqueta', '=', $identificador);
            
            if ($ciclo->id_proyecto) {
                $query->where('id_proyecto', '=', $ciclo->id_proyecto);
            }
            
            return $query->first();
        }

        // Auditoría
        return CrudActivo::where('etiqueta', '=', $identificador)
                         ->orWhere('codigo_cliente', '=', $identificador)
                         ->first();
    }

        public static function findByEtiquetaPadreAndCiclo($identificador, $cicloId)
    {
        if (!$cicloId) {
            return CrudActivo::where('etiqueta_padre', '=', $identificador)
                             ->orWhere('codigo_cliente', '=', $identificador)
                             ->first();
        }

        $ciclo = InvCiclo::find($cicloId);

        if (!$ciclo) {
            return null;
        }

        // Inventario
        if ($ciclo->idTipoCiclo == self::TIPO_INVENTARIO) {
            $query = Inventario::where('etiqueta_padre', '=', $identificador);
            
            if ($ciclo->id_proyecto) {
                $query->where('id_proyecto', '=', $ciclo->id_proyecto);
            }
            
            return $query->first();
        }

        // Auditoría
        return CrudActivo::where('etiqueta_padre', '=', $identificador)
                         ->orWhere('codigo_cliente', '=', $identificador)
                         ->first();
    }

    /**
     * Obtener ID del activo según tipo de ciclo
     * - Tipo 1: id_inventario
     * - Tipo 2: idActivo
     *
     * @param  string  $identificador
     * @param  int  $cicloId
     * @return int|null
     */
    // public static function getIdByEtiquetaAndCiclo($identificador, $cicloId)
    // {
    //     // Si cicloId es 0 o null, es scan de bienes -> ir a crud_activos 
    //     if (!$cicloId) {
    //         return CrudActivo::where('etiqueta', '=', $identificador)
    //                          ->orWhere('codigo_cliente', '=', $identificador)
    //                          ->value('idActivo');
    //     }

    //     $ciclo = InvCiclo::find($cicloId);

    //     if (!$ciclo) {
    //         return null;
    //     }

    //     // Inventario
    //     if ($ciclo->idTipoCiclo == self::TIPO_INVENTARIO) {
    //         $query = Inventario::where('etiqueta', '=', $identificador);
            
    //         if ($ciclo->id_proyecto) {
    //             $query->where('id_proyecto', '=', $ciclo->id_proyecto);
    //         }
            
    //         return $query->value('id_inventario');
    //     }

    //     // Auditoría
    //     return CrudActivo::where('etiqueta', '=', $identificador)
    //                      ->orWhere('codigo_cliente', '=', $identificador)
    //                      ->value('idActivo');
    // }

public static function getIdByEtiquetaAndCiclo($identificador, $cicloId)
{
    // Scan libre (sin ciclo)
    if (!$cicloId) {
        return CrudActivo::where('etiqueta', $identificador)
                         ->orWhere('codigo_cliente', $identificador)
                         ->value('idActivo');
    }

    $ciclo = InvCiclo::find($cicloId);

    if (!$ciclo) {
        return null;
    }

    // Inventario (tipo 1)
    if ($ciclo->idTipoCiclo == self::TIPO_INVENTARIO) {

        $query = Inventario::where('etiqueta', $identificador);

        if ($ciclo->id_proyecto) {
            $query->where('id_proyecto', $ciclo->id_proyecto);
        }

        return $query->value('id_inventario');
    }

    // Auditoría (tipo 2)
    if ($ciclo->idTipoCiclo == self::TIPO_AUDITORIA) {
        return CrudActivo::where('etiqueta', $identificador)
                         ->orWhere('codigo_cliente', $identificador)
                         ->value('idActivo');
    }

    return null;
}

    /**
     * Obtener id_proyecto del ciclo
     *
     * @param  int  $cicloId
     * @return int|null
     */
    public static function getIdProyectoByCiclo($cicloId)
    {
        return DB::table('inv_ciclos')
                 ->where('idCiclo', $cicloId)
                 ->value('id_proyecto');
    }

    /**
     * Verificar si existe un activo según tipo de ciclo
     * Retorna un array con [existsInv, existsCrud]
     *
     * @param  string  $etiqueta
     * @param  int  $cicloId
     * @return array [Inventario|null, CrudActivo|null]
     */
    public static function checkExistsByEtiquetaAndCiclo($etiqueta, $cicloId)
    {
        // Si cicloId es 0 o null, es scan de bienes -> ir a crud_activos
        if (!$cicloId) {
            $existsCrud = CrudActivo::where('etiqueta', $etiqueta)->first();
            return [null, $existsCrud];
        }

        $ciclo = InvCiclo::find($cicloId);
        
        if (!$ciclo) {
            return [null, null];
        }

        $id_proyecto = $ciclo->id_proyecto;

        // Si es inventario (tipo 1), solo buscar en inventario
        if ($ciclo->idTipoCiclo == self::TIPO_INVENTARIO) {
            $existsInv = Inventario::where('etiqueta', $etiqueta)
                                   ->where('id_proyecto', $id_proyecto)
                                   ->first();
            return [$existsInv, null];
        }

        // Si es auditoría (tipo 2), solo buscar en crud_activos
        $existsCrud = CrudActivo::where('etiqueta', $etiqueta)->first();
        return [null, $existsCrud];
    }
}
