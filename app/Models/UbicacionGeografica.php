<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Facades\DB;

class UbicacionGeografica extends Model
{
    use HasFactory;

    protected $primaryKey = 'idUbicacionGeo';

    protected $table = 'ubicaciones_geograficas';
    public $timestamps = false; // No usar timestamps en esta tabla
    public function region()
    {
        return $this->belongsTo(Region::class, 'region', 'idRegion');
    }

    public function comuna()
    {
        return $this->belongsTo(Comuna::class, 'comuna', 'idComuna');
    }

    public function activos()
    {
        return $this->hasMany(CrudActivo::class, 'ubicacionGeografica', 'idUbicacionGeo');
    }

    public function activos_with_cats_by_cycle($cycle_id)
    {


        $queryBuilder = CrudActivo::select('crud_activos.*')->join('inv_ciclos_puntos', 'crud_activos.ubicacionGeografica', 'inv_ciclos_puntos.idPunto')
            ->join('inv_ciclos', 'inv_ciclos.idCiclo', '=', 'inv_ciclos_puntos.idCiclo')
            ->join('inv_ciclos_categorias', function (JoinClause $join) {
                $join->on('inv_ciclos.idCiclo', '=', 'inv_ciclos_categorias.idCiclo')
                    ->on('crud_activos.id_familia', '=', 'inv_ciclos_categorias.id_familia');
            })
            ->where('inv_ciclos.idCiclo', '=', $cycle_id)
            ->where('inv_ciclos_puntos.idPunto', '=', $this->idUbicacionGeo);

        return $queryBuilder;
        // $sql = "SELECT 
        // crud_activos.*
        // FROM
        // inv_ciclos
        // INNER JOIN inv_ciclos_puntos ON inv_ciclos.idCiclo = inv_ciclos_puntos.idCiclo
        // INNER JOIN inv_ciclos_categorias ON inv_ciclos.idCiclo = inv_ciclos_categorias.idCiclo
        // INNER JOIN crud_activos 
        //     ON inv_ciclos_puntos.idPunto =  crud_activos.ubicacionGeografica 
        //         AND inv_ciclos_categorias.categoria1 = crud_activos.categoriaN1
        //         AND inv_ciclos_categorias.categoria2 = crud_activos.categoriaN2
        //         AND inv_ciclos_categorias.categoria3 = crud_activos.categoriaN3
        // WHERE inv_ciclos.idCiclo = ? AND inv_ciclos_puntos.idPunto = ? ";

        //return collect(DB::select($sql, [$cycle_id, $this->idUbicacionGeo]));
    }

    public function cats_by_cycle($cycle_id)
    {

        $sql = "SELECT 
        crud_activos.categoriaN1,
        crud_activos.categoriaN2,
        crud_activos.categoriaN3
        FROM
        inv_ciclos
        INNER JOIN inv_ciclos_puntos ON inv_ciclos.idCiclo = inv_ciclos_puntos.idCiclo
        INNER JOIN inv_ciclos_categorias ON inv_ciclos.idCiclo = inv_ciclos_categorias.idCiclo
        INNER JOIN crud_activos 
            ON inv_ciclos_puntos.idPunto =  crud_activos.ubicacionGeografica 
                AND inv_ciclos_categorias.id_familia = crud_activos.id_familia
        WHERE inv_ciclos.idCiclo = ? AND inv_ciclos_puntos.idPunto = ?
        GROUP BY crud_activos.categoriaN1, crud_activos.categoriaN2, crud_activos.categoriaN3 ";

        return collect(DB::select($sql, [$cycle_id, $this->idUbicacionGeo]));
    }

    public function zonasPunto()
    {
        return $this->hasMany(ZonaPunto::class, 'idAgenda', 'idUbicacionGeo');
    }

    /**
     * Get the responsibles for the address.
     */
    public function responsibles()
    {
        return $this->hasMany(Responsable::class, 'idUbicacionGeografica', 'idUbicacionGeo');
    }
}
