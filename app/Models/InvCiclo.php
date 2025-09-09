<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Query\JoinClause;
use App\Models\Inventario;
use App\Models\EmplazamientoN3;
use App\Models\EmplazamientoN1;
use App\Models\Emplazamiento;
use App\Models\InvUsuariosPunto;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;


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

public function ciclo_puntos_users($usuario = null, $ciclo_id = null)
{
    $query = $this->hasManyThrough(
        UbicacionGeografica::class, 
        InvUsuariosPunto::class,    
        'idCiclo',                  
        'idUbicacionGeo',           
        'idCiclo',                  
        'idPunto'                   
    );


    if ($usuario) {
        $query->where('inv_usuarios_puntos.usuario', $usuario);
    }


    if ($ciclo_id) {
        $query->where('inv_usuarios_puntos.idCiclo', $ciclo_id);
    }

    return $query;
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

 public function zoneEmplazamientosWithCats(Emplazamiento $zona)
{
    $sql = "
        SELECT 
            ubicaciones_n2.idUbicacionN2 AS idUbicacionN2,
            crud_activos.ubicacionGeografica AS punto,
            crud_activos.ubicacionOrganicaN2 AS emplazamiento
        FROM
            inv_ciclos
        INNER JOIN inv_ciclos_puntos 
            ON inv_ciclos.idCiclo = inv_ciclos_puntos.idCiclo
        INNER JOIN inv_ciclos_categorias 
            ON inv_ciclos.idCiclo = inv_ciclos_categorias.idCiclo
        INNER JOIN crud_activos 
            ON inv_ciclos_puntos.idPunto = crud_activos.ubicacionGeografica 
            AND inv_ciclos_categorias.id_familia = crud_activos.id_familia
        INNER JOIN ubicaciones_n2
            ON ubicaciones_n2.codigoUbicacion = crud_activos.ubicacionOrganicaN2
        WHERE 
            inv_ciclos.idCiclo = ?
            AND crud_activos.ubicacionGeografica = ?
            AND crud_activos.ubicacionOrganicaN2 LIKE ?
        GROUP BY 
            ubicaciones_n2.idUbicacionN2, 
            crud_activos.ubicacionGeografica, 
            crud_activos.ubicacionOrganicaN2";

    $likeCodigo = '%' . $zona->codigoUbicacion . '%';

    return collect(DB::select($sql, [$this->idCiclo, $zona->idAgenda, $likeCodigo]));
}

public function diferencias_por_direcciones($cycle_id, $idAgenda)
{
   $sql = "
       SELECT
   datos_cliente.id_direccion,  
    map_assets_categories.name AS categoria, 
    datos_cliente.q_teorico,
    inv_resumen.q_fisico,
    COALESCE(inv_resumen.q_fisico, 0) - COALESCE(datos_cliente.q_teorico, 0) AS diferencia
    
FROM 
    map_assets_categories 
LEFT JOIN (
    SELECT 
        ar.address_id AS id_direccion,
        mak.category_id AS category_id,
        mak.name AS categoria,
        COUNT(*) AS q_teorico 
    FROM 
        map_marker_assets mak
    INNER JOIN map_markers_levels_areas lev ON mak.id = lev.marker_id
    INNER JOIN map_polygonal_areas ar ON lev.area_id = ar.id
    WHERE 
        ar.address_id = ?
    GROUP BY ar.address_id, mak.name, mak.category_id
) AS datos_cliente 
    ON map_assets_categories.id = datos_cliente.category_id
LEFT JOIN (
    SELECT 
        id_familia, 
        idUbicacionGeo, 
        COUNT(*) AS q_fisico 
    FROM inv_inventario 
    WHERE idUbicacionGeo = ?
    GROUP BY id_familia, idUbicacionGeo
) AS inv_resumen 
    ON map_assets_categories.id = inv_resumen.id_familia
";
return DB::select($sql, [$idAgenda, $idAgenda]);
}


public function activos_with_cats_by_cycle_emplazamiento($cycle_id, $idAgenda)
{
   $sql = "
        SELECT 
            'D' AS nivel,
            d.descripcion AS descripcionUbicacion,
            d.direccion, 
            NULL AS codigoUbicacion,
            COUNT(inv.etiqueta) AS num_activos
        FROM ubicaciones_geograficas AS d
        LEFT JOIN inv_inventario AS inv
            ON inv.idUbicacionGeo = d.idUbicacionGeo
            AND inv.id_ciclo = ?
        WHERE d.idUbicacionGeo = ?
        GROUP BY d.descripcion, d.direccion

        UNION ALL

        SELECT 
            'N1' AS nivel,
            n1.descripcionUbicacion,
            NULL AS direccion,
            n1.codigoUbicacion,
            COUNT(inv.etiqueta) AS num_activos
        FROM ubicaciones_n1 AS n1
        LEFT JOIN inv_inventario AS inv
            ON inv.idUbicacionGeo = n1.idAgenda
            AND inv.codigoUbicacion_N1 = n1.codigoUbicacion
            AND inv.id_ciclo = ?
            AND (inv.codigoUbicacion_N2 IS NULL OR LENGTH(inv.codigoUbicacion_N2) < 2)
        WHERE n1.idAgenda = ? 
        GROUP BY n1.descripcionUbicacion, n1.codigoUbicacion

        UNION ALL

        SELECT 
            'N2' AS nivel,
            n2.descripcionUbicacion,
            NULL AS direccion,
            n2.codigoUbicacion,
            COUNT(inv.etiqueta) AS num_activos
        FROM ubicaciones_n2 AS n2
        LEFT JOIN inv_inventario AS inv
            ON inv.idUbicacionGeo = n2.idAgenda
            AND inv.codigoUbicacion_N2 = n2.codigoUbicacion
            AND inv.id_ciclo = ?
            AND (inv.codigoUbicacionN3 IS NULL OR LENGTH(inv.codigoUbicacionN3) < 2)
        WHERE n2.idAgenda = ?
        GROUP BY n2.descripcionUbicacion, n2.codigoUbicacion

        UNION ALL

        SELECT 
            'N3' AS nivel,
            n3.descripcionUbicacion,
            NULL AS direccion,
            n3.codigoUbicacion,
            COUNT(inv.etiqueta) AS num_activos
        FROM ubicaciones_n3 AS n3
        LEFT JOIN inv_inventario AS inv
            ON inv.idUbicacionGeo = n3.idAgenda
            AND inv.codigoUbicacionN3 = n3.codigoUbicacion
            AND inv.id_ciclo = ?
        WHERE n3.idAgenda = ?
        GROUP BY n3.descripcionUbicacion, n3.codigoUbicacion

        ORDER BY nivel, codigoUbicacion;
   ";

   return DB::select($sql, [
       $cycle_id, $idAgenda,   // Direccion
       $cycle_id, $idAgenda,   // Emplazamiento N1
       $cycle_id, $idAgenda,   // Emplazamiento N2
       $cycle_id, $idAgenda    // Emplazamiento N3
   ]);
}


    public function zoneSubEmplazamientosWithCats(EmplazamientoN3 $zona)
    {
        $sql = "

         SELECT DISTINCT
       ubicaciones_n3.idUbicacionN3 AS idUbicacionN3,
        ubicaciones_n1.idAgenda AS punto,
        ubicaciones_n3.codigoUbicacion AS emplazamiento
        FROM inv_ciclos
        INNER JOIN inv_ciclos_puntos ON inv_ciclos.idCiclo = inv_ciclos_puntos.idCiclo
        INNER JOIN ubicaciones_n1 ON inv_ciclos_puntos.idPunto = ubicaciones_n1.idAgenda
        INNER JOIN ubicaciones_n3 ON ubicaciones_n1.idAgenda = ubicaciones_n3.idAgenda AND ubicaciones_n1.codigoUbicacion = LEFT(ubicaciones_n3.codigoUbicacion, 2)
        INNER JOIN ubicaciones_n2 
        ON ubicaciones_n2.idUbicacionN2 = ubicaciones_n3.idUbicacionN3
        INNER JOIN inv_ciclos_categorias 
        ON inv_ciclos.idCiclo = inv_ciclos_categorias.idCiclo
        WHERE inv_ciclos.idCiclo = ?
        AND ubicaciones_n3.idAgenda = ?
        AND ubicaciones_n3.codigoUbicacion = LEFT(?, 6)

    ";

        return collect(DB::select($sql, [$this->idCiclo, $zona->idAgenda, $zona->codigoUbicacion]));
    }



     public function EmplazamientosWithCatsN1(EmplazamientoN1 $zona)
    {
        $sql = "
        SELECT DISTINCT
            ubicaciones_n1.idUbicacionN1 AS idUbicacionN1,
            ubicaciones_n1.idAgenda AS punto,
            ubicaciones_n1.codigoUbicacion AS emplazamiento
        FROM inv_ciclos
        INNER JOIN inv_ciclos_puntos 
            ON inv_ciclos.idCiclo = inv_ciclos_puntos.idCiclo
        INNER JOIN ubicaciones_n1 
            ON inv_ciclos_puntos.idPunto = ubicaciones_n1.idAgenda
        INNER JOIN inv_ciclos_categorias 
            ON inv_ciclos.idCiclo = inv_ciclos_categorias.idCiclo
        WHERE inv_ciclos.idCiclo = ?
            AND ubicaciones_n1.idAgenda = ?
    ";

        return collect(DB::select($sql, [$this->idCiclo, $zona->idAgenda]));
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

    public function emplazamientos_with_cats_inv()
    {
        $queryBuilder = Emplazamiento::select('ubicaciones_n2.*')
            ->distinct()
            ->join('inv_ciclos_puntos', 'ubicaciones_n2.idAgenda', 'inv_ciclos_puntos.idPunto')
            ->join('inv_ciclos', 'inv_ciclos.idCiclo', '=', 'inv_ciclos_puntos.idCiclo')
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

    public function bienesGrupoFamiliaByCycle()
    {
        $sql =
            "(

            SELECT 
                dp_grupos.descripcion_grupo AS descripcion_grupo,
                dp_familias.descripcion_familia AS descripcion_familia,
                dp_familias.id_grupo AS id_grupo,
                dp_familias.id_familia AS id_familia,
                inv_bienes_nuevos.idLista AS idLista,
                inv_bienes_nuevos.idAtributo AS idAtributo,
                inv_bienes_nuevos.idIndice AS idIndice,
                inv_bienes_nuevos.idProyecto AS idProyecto,
                inv_bienes_nuevos.descripcion AS descripcion,
                inv_bienes_nuevos.observacion AS observacion,
                inv_bienes_nuevos.listaRapida AS listaRapida,
                inv_bienes_nuevos.creadoPor AS creadoPor,
                inv_bienes_nuevos.modificadoPor AS modificadoPor,
                inv_bienes_nuevos.fechaCreacion AS fechaCreacion,
                inv_bienes_nuevos.fechaModificacion AS fechaModificacion,
                inv_bienes_nuevos.estado AS estado,
                inv_bienes_nuevos.foto AS foto,
                inv_bienes_nuevos.ciclo_inventario AS ciclo_inventario,
                CONCAT(dp_grupos.descripcion_grupo,'/',dp_familias.descripcion_familia) AS grupo_familia
            FROM dp_grupos
            INNER JOIN dp_familias 
                ON dp_grupos.id_grupo = dp_familias.id_grupo
            LEFT JOIN inv_ciclos_categorias 
                ON inv_ciclos_categorias.id_familia = dp_familias.id_familia
            AND inv_ciclos_categorias.id_grupo = dp_familias.id_grupo
            AND inv_ciclos_categorias.idCiclo = $this->idCiclo 
            INNER JOIN inv_bienes_nuevos 
                ON dp_familias.id_familia = inv_bienes_nuevos.id_familia
                AND inv_bienes_nuevos.idAtributo = 1
            WHERE IFNULL(inv_ciclos_categorias.id_familia, '0') <> '0'
        
        
        
            UNION ALL
        
        
            SELECT 
                dp_grupos.descripcion_grupo AS descripcion_grupo,
                dp_familias.descripcion_familia AS descripcion_familia,
                dp_familias.id_grupo AS id_grupo,
                dp_familias.id_familia AS id_familia,
                indices_listas.idLista AS idLista,
                indices_listas.idAtributo AS idAtributo,
                indices_listas.idIndice AS idIndice,
                indices_listas.idProyecto AS idProyecto,
                indices_listas.descripcion AS descripcion,
                indices_listas.observacion AS observacion,
                indices_listas.listaRapida AS listaRapida,
                indices_listas.creadoPor AS creadoPor,
                indices_listas.modificadoPor AS modificadoPor,
                indices_listas.fechaCreacion AS fechaCreacion,
                indices_listas.fechaModificacion AS fechaModificacion,
                indices_listas.estado AS estado,
                indices_listas.foto AS foto,
                indices_listas.ciclo_inventario AS ciclo_inventario,
                CONCAT(dp_grupos.descripcion_grupo,'/',dp_familias.descripcion_familia) AS grupo_familia
            FROM dp_grupos
            INNER JOIN dp_familias 
                ON dp_grupos.id_grupo = dp_familias.id_grupo
            LEFT JOIN inv_ciclos_categorias 
                ON inv_ciclos_categorias.id_familia = dp_familias.id_familia
            AND inv_ciclos_categorias.id_grupo = dp_familias.id_grupo
            AND inv_ciclos_categorias.idCiclo = $this->idCiclo 
            INNER JOIN indices_listas 
                ON dp_familias.id_familia = indices_listas.id_familia
                AND indices_listas.idAtributo = 1
            WHERE IFNULL(inv_ciclos_categorias.id_familia, '0') <> '0'
        
        ) AS bienes_grupos_familia_by_cycle
        
        ";

        return DB::table(DB::raw($sql));
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
            ->where('inv_ciclos.idCiclo', '=', $this->idCiclo)
            ->where('crud_activos.tipoCambio', '!=', 200); //Inventario



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
