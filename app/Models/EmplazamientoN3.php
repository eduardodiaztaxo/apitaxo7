<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Facades\DB;

class EmplazamientoN3 extends Model
{
    use HasFactory;

    protected $table = 'ubicaciones_n3';

    protected $primaryKey = 'idUbicacionN3';

    const CREATED_AT = 'fechaCreacion';
    const UPDATED_AT = 'fechaActualizacion';

    protected $fillable = [
        'idAgenda',
        'descripcionUbicacion',
        'codigoUbicacion',
        'estado',
        'fechaCreacion',
        'usuario',
        'newApp',
        'modo'
    ];

    public function subemplazamientosNivel3()
    {

        return $this->hasMany(EmplazamientoN3::class, 'idAgenda', 'idAgenda')->where('codigoUbicacion', $this->codigoUbicacion);
    }

    public function activos()
    {
        return $this->hasMany(CrudActivo::class, 'ubicacionOrganicaN3', 'codigoUbicacion')->where('ubicacionGeografica', $this->idAgenda);
    }


    public function inv_activos()
    {
        return $this->inv_activos_with_child_levels()->whereRaw('LENGTH(inv_inventario.codigoUbicacionN4) < 2');
    }

    public function inv_activos_with_child_levels()
    {
        return $this->hasMany(Inventario::class, 'idUbicacionN3', 'idUbicacionN3');
    }

    public function inv_group_families()
    {
        return $this->inv_group_families_with_child_levels()->whereRaw('LENGTH(inv_inventario.codigoUbicacionN4) < 2');
    }

    public function inv_group_families_with_child_levels()
    {

        $idUbicacionN3 = $this->idUbicacionN3;


        return Inventario::select(
            'ubicaciones_n3.codigoUbicacion',
            DB::raw("'n1' AS place_level"),
            DB::raw("'1' AS isSub"),
            'inv_inventario.id_ciclo',
            'inv_inventario.id_grupo',
            'inv_inventario.id_familia',
            'dp_grupos.descripcion_grupo',
            'dp_familias.descripcion_familia',
            DB::raw('COUNT(*) as total')
        )->join('ubicaciones_n3', function (JoinClause $join) use ($idUbicacionN3) {
            $join->on('inv_inventario.idUbicacionN3', '=', 'ubicaciones_n3.idUbicacionN3')
                ->on('ubicaciones_n3.idUbicacionN3', '=', DB::raw($idUbicacionN3));
        })
            ->join('dp_familias', 'inv_inventario.id_familia', 'dp_familias.id_familia')
            ->join('dp_grupos', 'inv_inventario.id_grupo', 'dp_grupos.id_grupo')
            ->groupBy('ubicaciones_n3.codigoUbicacion', 'inv_inventario.id_ciclo', 'inv_inventario.id_grupo', 'inv_inventario.id_familia', 'dp_grupos.descripcion_grupo', 'dp_familias.descripcion_familia');
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
            ->where('crud_activos.ubicacionOrganicaN3', '=', $this->codigoUbicacion)
            ->where('crud_activos.ubicacionGeografica', '=', $this->idAgenda)
            ->where('crud_activos.tipoCambio', '!=', 200); //Inventario
        return $queryBuilder;
    }

    public function zonaPunto()
    {
        return ZonaPunto::where('idAgenda', $this->idAgenda)
            ->where('codigoUbicacion', substr($this->codigoUbicacion, 0, 2));
    }

    public function ubicacionPunto()
    {
        return $this->belongsTo(UbicacionGeografica::class, 'idAgenda', 'idUbicacionGeo');
    }
}
