<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Facades\DB;

class Emplazamiento extends Model
{
    use HasFactory;

    protected $table = 'ubicaciones_n2';

    protected $primaryKey = 'idUbicacionN2';

    const CREATED_AT = 'fechaCreacion';
    const UPDATED_AT = 'fechaActualizacion';

    protected $fillable = [
        'idAgenda',
        'descripcionUbicacion',
        'codigoUbicacion',
        'estado',
        'usuario',
        'ciclo_auditoria'
    ];


    public function activos()
    {
        return $this->hasMany(CrudActivo::class, 'ubicacionOrganicaN2', 'codigoUbicacion')->where('ubicacionGeografica', $this->idAgenda);
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
            ->where('inv_ciclos_puntos.idPunto', '=', $this->idAgenda)
            ->where('crud_activos.ubicacionOrganicaN2', '=', $this->codigoUbicacion)
            ->where('crud_activos.ubicacionGeografica', '=', $this->idAgenda);




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
        // WHERE inv_ciclos.idCiclo = ? AND inv_ciclos_puntos.idPunto = ? AND crud_activos.ubicacionOrganicaN2 = ? AND crud_activos.ubicacionGeografica = ? ";

        return $queryBuilder;
    }

    public function zonaPunto()
    {
        return $this->belongsTo(ZonaPunto::class, 'idAgenda', 'idAgenda')->where('codigoUbicacion', '=', substr($this->codigoUbicacion, 0, 2));
    }

    public function ubicacionPunto()
    {
        return $this->belongsTo(UbicacionGeografica::class, 'idAgenda', 'idUbicacionGeo');
    }
}
