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
        return [
            'codigo' => $this->codigo,
            'codigoUbicacion' => $this->codigoUbicacion,
            'descripcionUbicacion' => $this->descripcionUbicacion,
            'idAgenda' => $this->idAgenda,
            'idUbicacionN1' => $this->idUbicacionN1,
            'totalBienes' => $this->totalBienes,
            'num_activos'   => $this->activos()->get()->count()
        ];
    }
}
