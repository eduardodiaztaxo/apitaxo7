<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Models\CiclosPunto;
use App\Models\InvConteoRegistro;
use App\Models\UbicacionGeografica;
use App\Models\Inventario;
use App\Models\PuntosEstados;
use App\Models\CrudActivo;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Facades\Auth;

class UbicacionGeograficaDireccionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
    
        $zone = [
            'codigo' => $this->codigo,
            'codigoUbicacion' => $this->idUbicacionGeo,
            'descripcionUbicacion' => $this->descripcion,
            'descripcionDireccion' => $this->direccion,
            'idAgenda' => $this->idAgenda,
            'idUbicacionN1' => $this->idUbicacionN1,
            'ciclo_auditoria' => (int)$this->ciclo_auditoria,
            'totalBienes' => $this->totalBienes,
            'num_activos' => $this->activos()->get()->count(),
            'num_activos_cats_by_cycle' => $this->activos_with_cats_by_cycleDireccion($this->cycle_id)[0]->total ?? 0,
            'num_activos_inv' => $this->activos_inv_by_cycle($this->ciclo_auditoria, $this->codigoUbicacion)->get()->count(),
        ];
        $zone['ubicacionPunto'] = $this->punto($this->codigoUbicacion);
    
        $zone['num_activos_orphans'] = $this->activos_with_cats_without_emplazamientos_by_cycle2($this->cycle_id)->count();
        $zone['num_total_orphans'] = $this->activos_without_emplazamientos2()->count();
        $zone['activos_orphans'] = $this->activos_with_cats_without_emplazamientos_by_cycle2($this->cycle_id)->get();
    
        return $zone;
    }
    

    public function activos_with_cats_inv_by_cycle($cycle_id)
    {
        $queryBuilder = Inventario::select('inv_inventario.*')
            ->where('inv_inventario.id_ciclo', '=', $cycle_id);

        return $queryBuilder;
    }

    public function activos_inv_by_cycle($cycle_id, $idUbicacionGeo)
    {
        $queryBuilder1 = Inventario::select('inv_inventario.*')
            ->where('inv_inventario.id_ciclo', '=', $cycle_id)
            ->where('codigoUbicacion', '=', $idUbicacionGeo);

        return $queryBuilder1;
    }

    public function punto($idUbicacionGeo)
    {
        $queryBuilder = DB::select("
            SELECT ubicaciones_geograficas.*, 
                   comunas.descripcion AS descripcionComuna,
                   regiones.descripcion AS descripcionRegion
            FROM ubicaciones_geograficas
            JOIN comunas ON ubicaciones_geograficas.comuna = comunas.idComuna
            JOIN regiones ON ubicaciones_geograficas.region = regiones.idRegion
            WHERE ubicaciones_geograficas.idUbicacionGeo = :idUbicacionGeo
        ", [
            'idUbicacionGeo' => $this->idUbicacionGeo, 
        ]);
    
        return $queryBuilder;
    }
    

    public function activos_with_cats_by_cycleDireccion($cycle_id)
    {
        $queryBuilder = DB::select("
            SELECT COUNT(*) AS total
            FROM crud_activos
            JOIN inv_ciclos_puntos ON crud_activos.ubicacionGeografica = inv_ciclos_puntos.idPunto
            JOIN inv_ciclos ON inv_ciclos.idCiclo = inv_ciclos_puntos.idCiclo
            JOIN inv_ciclos_categorias ON inv_ciclos.idCiclo = inv_ciclos_categorias.idCiclo
                AND crud_activos.id_familia = inv_ciclos_categorias.id_familia
            LEFT JOIN ubicaciones_geograficas ON crud_activos.ubicacionGeografica = ubicaciones_geograficas.idUbicacionGeo
            WHERE inv_ciclos.idCiclo = :cycle_id
                AND crud_activos.ubicacionGeografica = :ubicacionGeografica
        ", [
            'cycle_id' => $cycle_id,
            'ubicacionGeografica' => $this->idUbicacionGeo,
        ]);
    
        return $queryBuilder;
    }


public function activos_with_cats_without_emplazamientos_by_cycle2($cycle_id)
{
    // Subquery para la última foto por activo, trayendo también 'picture'
    $subquery = DB::table('crud_activos_pictures as cap')
        ->select('cap.id_activo', 'cap.url_picture', 'cap.picture')
        ->whereRaw('cap.id_foto = (SELECT MAX(id_foto) FROM crud_activos_pictures WHERE id_activo = cap.id_activo)');

    $queryBuilder = CrudActivo::select(
            'crud_activos.*',
            'indices_listas.descripcion AS descripcionActivo',
            'dp_familias.descripcion_familia AS descripcionFamilia',
            DB::raw("COALESCE(CONCAT(fotos.url_picture, '/', fotos.picture), 'https://api.taxochile.cl/img/notavailable.jpg') AS foto_url")
        )
        ->join('inv_ciclos_puntos', 'crud_activos.ubicacionGeografica', '=', 'inv_ciclos_puntos.idPunto')
        ->join('inv_ciclos', 'inv_ciclos.idCiclo', '=', 'inv_ciclos_puntos.idCiclo')
        ->join('inv_ciclos_categorias', function (JoinClause $join) {
            $join->on('inv_ciclos.idCiclo', '=', 'inv_ciclos_categorias.idCiclo')
                ->on('crud_activos.id_familia', '=', 'inv_ciclos_categorias.id_familia');
        })
        ->leftJoin('ubicaciones_geograficas', function (JoinClause $join) {
            $join->on('crud_activos.ubicacionGeografica', '=', 'ubicaciones_geograficas.idUbicacionGeo');
        })
        ->leftJoin('indices_listas', function (JoinClause $join) {
            $join->on('indices_listas.idIndice', '=', 'crud_activos.idIndice')
                 ->on('indices_listas.idLista', '=', 'crud_activos.nombreActivo')
                 ->on('indices_listas.idAtributo', '=', DB::raw(1));
        })
        ->leftJoin('dp_familias', function (JoinClause $join) {
            $join->on('dp_familias.id_familia', '=', 'crud_activos.id_familia');
        })
        // Join al subquery de la última foto
        ->leftJoinSub($subquery, 'fotos', function ($join) {
            $join->on('fotos.id_activo', '=', 'crud_activos.idActivo');
        })
        ->where('inv_ciclos.idCiclo', '=', $cycle_id)
        ->where('crud_activos.ubicacionGeografica', '=', $this->idUbicacionGeo);

    return $queryBuilder;
}

    public function activos_without_emplazamientos2()
    {
        $queryBuilder = CrudActivo::select('crud_activos.*')
            ->leftJoin('ubicaciones_geograficas', function (JoinClause $join) {
                $join->on('crud_activos.ubicacionGeografica', '=', 'ubicaciones_geograficas.idUbicacionGeo');
            })
            ->where(function (Builder $query) {
                $query->whereNull('crud_activos.ubicacionOrganicaN2')
                    ->orWhereNull('ubicaciones_geograficas.idUbicacionGeo');
            })
            ->where('crud_activos.ubicacionGeografica', '=', $this->idUbicacionGeo);

        return $queryBuilder;
    }
}