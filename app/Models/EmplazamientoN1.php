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
        'idProyecto',
        'idAgenda',
        'descripcionUbicacion',
        'codigoUbicacion',
        'estado',
        'fechaCreacion',
        'usuario',
        'ciclo_auditoria',
        'newApp',
        'modo'
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


    public function inv_activos()
    {
        return $this->inv_activos_with_child_levels()->whereRaw('LENGTH(inv_inventario.codigoUbicacion_N2) < 2');
    }

    public function inv_activos_with_child_levels()
    {

        return Inventario::join('ubicaciones_n1', function (JoinClause $join) {
            $join->on('inv_inventario.idUbicacionGeo', '=', 'ubicaciones_n1.idAgenda')
                ->on('inv_inventario.codigoUbicacion_N1', '=', 'ubicaciones_n1.codigoUbicacion');
        })
            ->where('inv_inventario.codigoUbicacion_N1', '=', $this->codigoUbicacion)
            ->where('ubicaciones_n1.idUbicacionN1', '=', $this->idUbicacionN1);
        //return $this->hasMany(Inventario::class, 'idUbicacionN1', 'idUbicacionN1');
    }

    public function inv_group_families()
    {
        return $this->inv_group_families_with_child_levels()->whereRaw('LENGTH(inv_inventario.codigoUbicacion_N2) < 2');
    }

    public function inv_group_families_with_child_levels()
    {



        return Inventario::select(
            'ubicaciones_n1.codigoUbicacion',
            DB::raw("'n1' AS place_level"),
            DB::raw("'1' AS isSub"),
            'inv_inventario.id_ciclo',
            'inv_inventario.id_grupo',
            'inv_inventario.id_familia',
            'dp_grupos.descripcion_grupo',
            'dp_familias.descripcion_familia',
            DB::raw('COUNT(*) as total')
        )->join('ubicaciones_n1', function (JoinClause $join) {
            $join->on('inv_inventario.idUbicacionGeo', '=', 'ubicaciones_n1.idAgenda')
                ->on('inv_inventario.codigoUbicacion_N1', '=', 'ubicaciones_n1.codigoUbicacion');
        })
            ->join('dp_familias', 'inv_inventario.id_familia', 'dp_familias.id_familia')
            ->join('dp_grupos', 'inv_inventario.id_grupo', 'dp_grupos.id_grupo')
            ->where('ubicaciones_n1.idUbicacionN1', '=', $this->idUbicacionN1)
            ->groupBy('ubicaciones_n1.codigoUbicacion', 'inv_inventario.id_ciclo', 'inv_inventario.id_grupo', 'inv_inventario.id_familia', 'dp_grupos.descripcion_grupo', 'dp_familias.descripcion_familia');
    }



    public function activos()
    {
        return $this->hasMany(CrudActivo::class, 'ubicacionOrganicaN1', 'codigoUbicacion')->where('ubicacionGeografica', $this->idAgenda);
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


    public function zonaPunto()
    {
        return $this->where('idUbicacionN1', $this->idUbicacionN1);
    }


    public function ubicacionPunto()
    {
        return $this->belongsTo(UbicacionGeografica::class, 'idAgenda', 'idUbicacionGeo');
    }
}
