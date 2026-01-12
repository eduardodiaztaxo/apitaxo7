<?php

namespace App\Http\Resources\V1;

use App\Models\Inventario;
use App\Models\CrudActivo;
use Illuminate\Http\Resources\Json\JsonResource;

class ZonaPuntoResource extends JsonResource
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
            'codigoUbicacion' => $this->codigoUbicacion,
            'descripcionUbicacion' => $this->descripcionUbicacion,
            'idAgenda' => $this->idAgenda,
            'idUbicacionN1' => $this->idUbicacionN1,
            'ciclo_auditoria' => (int)$this->ciclo_auditoria,
            'totalBienes' => $this->totalBienes,
            'num_activos'   => $this->activos()->count(),
            'num_activos_cats_by_cycle' => 0,
            'num_activos_inv' => $this->activos_inv_by_cycle($this->ciclo_auditoria, $this->idAgenda, $this->codigoUbicacion)->count(),
        ];


        if (isset($this->requireAddress) && $this->requireAddress) {
            $zone['ubicacionPunto'] = UbicacionGeograficaResource::make($this->punto()->first());
        }

        if (isset($this->cycle_id) && $this->cycle_id) {
            $zone['num_activos_cats_by_cycle'] = $this->activos_with_cats_by_cycle($this->cycle_id)->count() + $this->activos_with_cats_inv_by_cycle($this->cycle_id)->count();
            $zone['num_activos_orphans'] = $this->activos_with_cats_without_emplazamientos_by_cycle($this->cycle_id)->count();
            $zone['num_total_orphans'] = $this->activos_without_emplazamientos()->count();

            if (isset($this->requireOrphanAssets) && $this->requireOrphanAssets) {
                $zone['activos_orphans'] = CrudActivoResource::collection($this->activos_with_cats_without_emplazamientos_by_cycle($this->cycle_id)->get());
            }
        } else {
            if (isset($this->requireOrphanAssets) && $this->requireOrphanAssets) {
                $zone['activos_orphans'] = CrudActivoResource::collection($this->activos_without_emplazamientos()->get());
            }
        }
        return $zone;
    }
    public function activos_with_cats_inv_by_cycle($cycle_id)
    {
        $queryBuilder = Inventario::select('inv_inventario.*')
            ->where('inv_inventario.id_ciclo', '=', $cycle_id);

        return $queryBuilder;
    }
    public function activos_inv_by_cycle($ciclo_auditoria, $idAgenda, $codigoUbicacion)
    {
        $byN1 = Inventario::select('inv_inventario.*')
            ->where('inv_inventario.id_ciclo', '=', $ciclo_auditoria)
            ->where('idUbicacionGeo', '=', $idAgenda)
            ->where('codigoUbicacion_n1', $codigoUbicacion);

        $byN3 = Inventario::select('inv_inventario.*')
            ->where('inv_inventario.id_ciclo', '=', $ciclo_auditoria)
            ->where('idUbicacionGeo', '=', $idAgenda)
            ->where('codigoUbicacionN3', 'LIKE', $codigoUbicacion . '%');

        return $byN1->union($byN3);
    }
}
