<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Resources\Json\JsonResource;

class EmplazamientoResource extends JsonResource
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
            'id' => $this->idUbicacionN2,
            'codigo' => $this->codigo,
            'codigoUbicacion' => $this->codigoUbicacion,
            'nombre' => $this->descripcionUbicacion,
            'idAgenda' => $this->idAgenda,
            'idUbicacionN2' => $this->idUbicacionN2,
            'num_activos'   => $this->activos()->get()->count(),
            'num_categorias' => $this->activos()->select('categoriaN3')->groupBy('categoriaN3')->get()->count(),
            'zone_address' => ZonaPuntoResource::make($this->zonaPunto()->first())
        ];
    }
}
