<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Emplazamiento;
use App\Models\EmplazamientoN3;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Facades\DB;

class EmplazamientoN1 extends Model
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
        'ciclo_auditoria'
    ];
            public function subemplazamientos()
        {
            
            return $this->hasMany(EmplazamientoN1::class, 'idAgenda', 'idAgenda')->where('codigoUbicacion', $this->codigoUbicacion);

        }

        public function emplazamientosN2()
        {
            return $this->hasMany(Emplazamiento::class, 'idAgenda', 'idAgenda');
        }
        public function emplazamientosN3()
        {
            return $this->hasMany(EmplazamientoN3::class, 'idAgenda', 'idAgenda');
        }


    public function activos()
    {
        return $this->hasMany(CrudActivo::class, 'ubicacionOrganicaN3', 'codigoUbicacion')->where('ubicacionGeografica', $this->idAgenda);
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
            ->where('crud_activos.ubicacionOrganicaN1', '=', $this->codigoUbicacion)
            ->where('crud_activos.ubicacionGeografica', '=', $this->idAgenda)
            ->where('crud_activos.tipoCambio', '!=', 200); //Inventario
        return $queryBuilder;
    }


       public function zoneEmplazamientosN1()
{

    return $this->hasMany(EmplazamientoN1::class, 'idAgenda', 'idAgenda');

}
    public function ubicacionPunto()
    {
        return $this->belongsTo(UbicacionGeografica::class, 'idAgenda', 'idUbicacionGeo');
    }

}
