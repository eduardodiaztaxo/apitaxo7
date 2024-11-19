<?php

namespace App\Http\Resources\V1;

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
            'totalBienes' => $this->totalBienes,
            'num_activos'   => $this->activos()->get()->count(),
            'num_activos_cats_by_cycle' => 0
        ];

        if (isset($this->cycle_id) && $this->cycle_id) {
            $zone['num_activos_cats_by_cycle'] = $this->activos_with_cats_by_cycle($this->cycle_id)->count();
        }

        return $zone;
    }
}
