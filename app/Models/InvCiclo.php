<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\JoinClause;
use App\Models\Inventario;
use Illuminate\Support\Facades\DB;

class InvCiclo extends Model
{
    use HasFactory;

    protected $primaryKey = 'idCiclo';

    public function puntos()
    {
        //return $this->hasMany(UbicacionGeografica::class, 'idPunto', 'idPunto');

        return $this->hasManyThrough(
            UbicacionGeografica::class,
            InvCicloPunto::class,
            'idCiclo',
            'idUbicacionGeo',
            'idCiclo',
            'idPunto'
        );
    }

    public function ciclo_users()
    {
        return $this->hasMany(InvCicloUser::class, 'ciclo_id', 'idCiclo');
    }

    public function zonesWithCats()
    {

        $sql = "SELECT 
        crud_activos.ubicacionGeografica AS punto,
        crud_activos.ubicacionOrganicaN1 AS zona
        FROM
        inv_ciclos
        INNER JOIN inv_ciclos_puntos ON inv_ciclos.idCiclo = inv_ciclos_puntos.idCiclo
        INNER JOIN inv_ciclos_categorias ON inv_ciclos.idCiclo = inv_ciclos_categorias.idCiclo
        INNER JOIN crud_activos 
            ON inv_ciclos_puntos.idPunto =  crud_activos.ubicacionGeografica 
                AND inv_ciclos_categorias.id_familia = crud_activos.id_familia
                
        WHERE inv_ciclos.idCiclo = ?
        GROUP BY crud_activos.ubicacionGeografica, crud_activos.ubicacionOrganicaN1 ";

        return collect(DB::select($sql, [$this->idCiclo]));
    }

    /**
     * Get Emplazamientos By Zone and Cycle.
     *
     * @param  \App\Models\ZonaPunto  $zona
     * @return \Illuminate\Support\Collection
     */

    public function zoneEmplazamientosWithCats(ZonaPunto $zona)
    {
        $sql = "
        SELECT 
        ubicaciones_n2.idUbicacionN2 AS idUbicacionN2,
        crud_activos.ubicacionGeografica AS punto,
        crud_activos.ubicacionOrganicaN2 AS emplazamiento
        FROM
        inv_ciclos
        INNER JOIN inv_ciclos_puntos ON inv_ciclos.idCiclo = inv_ciclos_puntos.idCiclo
        INNER JOIN ubicaciones_n1 ON inv_ciclos_puntos.idPunto = ubicaciones_n1.idAgenda
        INNER JOIN ubicaciones_n2 ON ubicaciones_n1.idAgenda = ubicaciones_n2.idAgenda AND ubicaciones_n1.codigoUbicacion = LEFT(ubicaciones_n2.codigoUbicacion, 2)
        INNER JOIN inv_ciclos_categorias ON inv_ciclos.idCiclo = inv_ciclos_categorias.idCiclo
        INNER JOIN crud_activos 
            ON inv_ciclos_puntos.idPunto =  crud_activos.ubicacionGeografica 
            AND ubicaciones_n1.codigoUbicacion = crud_activos.ubicacionOrganicaN1
            AND inv_ciclos_categorias.id_familia = crud_activos.id_familia
        WHERE inv_ciclos.idCiclo = ? AND crud_activos.ubicacionGeografica = ? AND crud_activos.ubicacionOrganicaN1 = ?
        GROUP BY ubicaciones_n2.idUbicacionN2, crud_activos.ubicacionGeografica, crud_activos.ubicacionOrganicaN2 ";

        return collect(DB::select($sql, [$this->idCiclo, $zona->idAgenda, $zona->codigoUbicacion]));
    }


    public function emplazamientos_with_cats()
    {

        $queryBuilder = Emplazamiento::select('ubicaciones_n2.*')
            ->distinct()
            ->join('inv_ciclos_puntos', 'ubicaciones_n2.idAgenda', 'inv_ciclos_puntos.idPunto')
            ->join('inv_ciclos', 'inv_ciclos.idCiclo', '=', 'inv_ciclos_puntos.idCiclo')
            ->join('crud_activos', 'inv_ciclos_puntos.idPunto', 'crud_activos.ubicacionGeografica')
            ->join('inv_ciclos_categorias', function (JoinClause $join) {
                $join->on('inv_ciclos.idCiclo', '=', 'inv_ciclos_categorias.idCiclo')
                    ->on('crud_activos.id_familia', '=', 'inv_ciclos_categorias.id_familia');
            })
            ->where('inv_ciclos.idCiclo', '=', $this->idCiclo);



        return $queryBuilder;
    }

    /**
     * Gets Category IDs By Cycle
     * 
     * @return \Illuminate\Support\Collection
     */

    public function getCatsIDs()
    {
        $sql = "SELECT 
        inv_ciclos.idCiclo AS idCiclo,
        inv_ciclos_categorias.id_grupo AS id_grupo,
        inv_ciclos_categorias.id_familia AS id_familia,
        inv_ciclos_categorias.categoria3 AS categoria3
        FROM
        inv_ciclos
        INNER JOIN inv_ciclos_categorias ON inv_ciclos.idCiclo = inv_ciclos_categorias.idCiclo
        WHERE inv_ciclos.idCiclo = ? ";

        return collect(DB::select($sql, [$this->idCiclo]));
    }



    public function activos_with_cats()
    {

        $queryBuilder = CrudActivo::select('crud_activos.*')
            ->distinct()
            ->join('inv_ciclos_puntos', 'crud_activos.ubicacionGeografica', 'inv_ciclos_puntos.idPunto')
            ->join('inv_ciclos', 'inv_ciclos.idCiclo', '=', 'inv_ciclos_puntos.idCiclo')
            ->join('inv_ciclos_categorias', function (JoinClause $join) {
                $join->on('inv_ciclos.idCiclo', '=', 'inv_ciclos_categorias.idCiclo')
                    ->on('crud_activos.id_familia', '=', 'inv_ciclos_categorias.id_familia');
            })
            ->where('inv_ciclos.idCiclo', '=', $this->idCiclo);



        return $queryBuilder;
    }

    public function activos_with_cats_inv()
    {

        $queryBuilder = Inventario::select('inv_inventario.*')
            ->where('inv_inventario.id_ciclo', '=', $this->idCiclo);

        return $queryBuilder;
    }

    public function audit_activos_address_cats()
    {

        $queryBuilder = CrudActivo::select('crud_activos.*')->join('inv_ciclos_puntos', 'crud_activos.ubicacionGeografica', 'inv_ciclos_puntos.idPunto')
            ->join('inv_ciclos', 'inv_ciclos.idCiclo', '=', 'inv_ciclos_puntos.idCiclo')
            ->join('inv_ciclos_categorias', function (JoinClause $join) {
                $join->on('inv_ciclos.idCiclo', '=', 'inv_ciclos_categorias.idCiclo')
                    ->on('crud_activos.id_familia', '=', 'inv_ciclos_categorias.id_familia');
            })
            ->join('inv_conteo_registro', function (JoinClause $join) {
                $join->on('crud_activos.etiqueta', '=', 'inv_conteo_registro.etiqueta')
                    ->on('inv_ciclos.idCiclo', '=', 'inv_conteo_registro.ciclo_id');
            })
            ->where('inv_ciclos.idCiclo', '=', $this->idCiclo)
            ->where('inv_conteo_registro.status', '=', 1);



        return $queryBuilder;
    }


    public function dump()
    {
        return $this->hasMany(DbAuditsDump::class, 'cycle_id', 'idCiclo');
    }
}
