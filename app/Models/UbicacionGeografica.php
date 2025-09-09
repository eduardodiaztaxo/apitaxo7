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

    protected $fillable = [
        'q_poligono',
    ];

    public function region()
    {
        return $this->belongsTo(Region::class, 'region', 'idRegion');
    }

    public function comuna()
    {
        return $this->belongsTo(Comuna::class, 'comuna', 'idComuna');
    }

    public function inv_activos()
    {
        return $this->inv_activos_with_child_levels();
    }

    public function inv_activos_with_child_levels()
    {
        return $this->hasMany(Inventario::class, 'idUbicacionGeo', 'idUbicacionGeo');
    }

    public function inv_group_families()
    {
        return $this->inv_group_families_with_child_levels();
    }

    public function inv_group_families_with_child_levels()
    {
        $idUbicacionGeo = $this->idUbicacionGeo;
        //MODIFICADO
        return Inventario::select(
            DB::raw("'00' AS codigoUbicacion"),
            DB::raw("'n0' AS place_level"),
            DB::raw("'0' AS isSub"),
            'inv_inventario.id_ciclo',
            'inv_inventario.id_grupo',
            'inv_inventario.id_familia',
            'dp_grupos.descripcion_grupo',
            'dp_familias.descripcion_familia',
            DB::raw('COUNT(*) as total')
        )->leftJoin('ubicaciones_geograficas', 'inv_inventario.idUbicacionGeo', 'ubicaciones_geograficas.idUbicacionGeo')
            ->leftJoin('dp_familias', 'inv_inventario.id_familia', 'dp_familias.id_familia')
            ->leftJoin('dp_grupos', 'inv_inventario.id_grupo', 'dp_grupos.id_grupo')
            ->where('inv_inventario.idUbicacionGeo', '=', $idUbicacionGeo)
            ->groupBy('codigoUbicacion', 'inv_inventario.id_ciclo', 'inv_inventario.id_grupo', 'inv_inventario.id_familia', 'dp_grupos.descripcion_grupo', 'dp_familias.descripcion_familia');
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


public function verificacion_range($idAgenda)
{
   $sql = "
    SELECT NAME, AREA, address_id FROM map_polygonal_areas
    WHERE address_id = ?
";
return DB::select($sql, [$idAgenda]);
}

}
