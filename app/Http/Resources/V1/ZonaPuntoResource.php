<?php

namespace App\Http\Resources\V1;

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
            'num_activos'   => $this->activos()->get()->count(),
            'num_activos_cats_by_cycle' => 0
        ];


        if (isset($this->requireAddress) && $this->requireAddress) {
            $zone['ubicacionPunto'] = UbicacionGeograficaResource::make($this->punto()->first());
        }

        if (isset($this->cycle_id) && $this->cycle_id) {
            $zone['num_activos_cats_by_cycle'] = $this->activos_with_cats_by_cycle($this->cycle_id)->count();
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
}
