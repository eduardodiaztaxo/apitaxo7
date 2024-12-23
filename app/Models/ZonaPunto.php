<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Facades\DB;

class ZonaPunto extends Model
{
    use HasFactory;

    protected $table = 'ubicaciones_n1';

    protected $primaryKey = 'idUbicacionN1';


    const CREATED_AT = 'fechaCreacion';
    const UPDATED_AT = 'fechaActualizacion';

    protected $fillable = [
        'idAgenda',
        'descripcionUbicacion',
        'codigoUbicacion',
        'estado',
        'usuario',
    ];


    public function activos()
    {
        //return $this->hasMany(CrudActivo::class, 'ubicacionOrganicaN1,ubicacionGeografica', 'idUbicacionN1,idAgenda');
        return $this->hasMany(CrudActivo::class, 'ubicacionOrganicaN1', 'codigoUbicacion')->where('ubicacionGeografica', $this->idAgenda);
    }

    public function punto()
    {
        return $this->belongsTo(UbicacionGeografica::class, 'idAgenda', 'idUbicacionGeo');
    }



    public function activos_with_cats_by_cycle($cycle_id)
    {



        $queryBuilder = CrudActivo::select('crud_activos.*')->join('inv_ciclos_puntos', 'crud_activos.ubicacionGeografica', 'inv_ciclos_puntos.idPunto')
            ->join('inv_ciclos', 'inv_ciclos.idCiclo', '=', 'inv_ciclos_puntos.idCiclo')
            ->join('inv_ciclos_categorias', function (JoinClause $join) {
                $join->on('inv_ciclos.idCiclo', '=', 'inv_ciclos_categorias.idCiclo')
                    ->on('crud_activos.categoriaN1', '=', 'inv_ciclos_categorias.categoria1')
                    ->on('crud_activos.categoriaN2', '=', 'inv_ciclos_categorias.categoria2')
                    ->on('crud_activos.categoriaN3', '=', 'inv_ciclos_categorias.categoria3');
            })
            ->where('inv_ciclos.idCiclo', '=', $cycle_id)
            ->where('inv_ciclos_puntos.idPunto', '=', $this->idAgenda)
            ->where('crud_activos.ubicacionOrganicaN1', '=', $this->codigoUbicacion)
            ->where('crud_activos.ubicacionGeografica', '=', $this->idAgenda);




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
        // WHERE inv_ciclos.idCiclo = ? AND inv_ciclos_puntos.idPunto = ? AND crud_activos.ubicacionOrganicaN1 = ? AND crud_activos.ubicacionGeografica = ? ";

        // return collect(DB::select($sql, [$cycle_id, $this->idAgenda, $this->codigoUbicacion, $this->idAgenda]));
    }

    public function activos_with_cats_without_emplazamientos_by_cycle($cycle_id)
    {



        $queryBuilder = CrudActivo::select('crud_activos.*')->join('inv_ciclos_puntos', 'crud_activos.ubicacionGeografica', 'inv_ciclos_puntos.idPunto')
            ->join('inv_ciclos', 'inv_ciclos.idCiclo', '=', 'inv_ciclos_puntos.idCiclo')
            ->join('inv_ciclos_categorias', function (JoinClause $join) {
                $join->on('inv_ciclos.idCiclo', '=', 'inv_ciclos_categorias.idCiclo')
                    ->on('crud_activos.categoriaN1', '=', 'inv_ciclos_categorias.categoria1')
                    ->on('crud_activos.categoriaN2', '=', 'inv_ciclos_categorias.categoria2')
                    ->on('crud_activos.categoriaN3', '=', 'inv_ciclos_categorias.categoria3');
            })
            ->where('inv_ciclos.idCiclo', '=', $cycle_id)
            ->where('inv_ciclos_puntos.idPunto', '=', $this->idAgenda)
            ->where('crud_activos.ubicacionOrganicaN1', '=', $this->codigoUbicacion)
            ->whereNull('crud_activos.ubicacionOrganicaN2')
            ->where('crud_activos.ubicacionGeografica', '=', $this->idAgenda);




        return $queryBuilder;
    }


    public function activos_without_emplazamientos()
    {



        $queryBuilder = CrudActivo::select('crud_activos.*')
            ->where('crud_activos.ubicacionOrganicaN1', '=', $this->codigoUbicacion)
            ->whereNull('crud_activos.ubicacionOrganicaN2')
            ->where('crud_activos.ubicacionGeografica', '=', $this->idAgenda);




        return $queryBuilder;
    }


    public function emplazamientos()
    {
        return $this->hasMany(Emplazamiento::class, 'idAgenda', 'idAgenda')->where('codigoUbicacion', 'LIKE', $this->codigoUbicacion . '%');
    }
}
