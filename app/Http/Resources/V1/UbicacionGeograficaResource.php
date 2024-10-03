<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Resources\Json\JsonResource;

class UbicacionGeograficaResource extends JsonResource
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
            'idUbicacionGeo' => $this->idUbicacionGeo,
            'codigoCliente' => $this->codigoCliente,
            'descripcion'   => $this->descripcion,
            'zona'          => $this->zona,
            'region'        => $this->region()->first()->descripcion,
            'ciudad'        => $this->ciudad,
            'comuna'        => $this->comuna()->first()->descripcion,
            'direccion'     => $this->direccion,
            'idPunto'       => $this->idPunto,
            'estadoGeo'     => $this->estadoGeo,
            'num_activos'   => $this->activos()->get()->count()
        ];
    }
}
