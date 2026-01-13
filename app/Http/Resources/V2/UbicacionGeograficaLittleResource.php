<?php

namespace App\Http\Resources\V2;

use Illuminate\Http\Resources\Json\JsonResource;

class UbicacionGeograficaLittleResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {


        $address = [
            'idUbicacionGeo' => $this->idUbicacionGeo,
            'codigoCliente' => $this->codigoCliente,
            'descripcion'   => $this->descripcion,
            'zona'          => $this->zona,
            'region'        => $this->region()->first()?->descripcion,
            'ciudad'        => $this->ciudad,
            'comuna'        => $this->comuna()->first()?->descripcion,
            'direccion'     => $this->direccion,
        ];


        return $address;
    }
}
