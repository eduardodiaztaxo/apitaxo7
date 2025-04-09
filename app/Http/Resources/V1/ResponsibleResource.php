<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Resources\Json\JsonResource;

class ResponsibleResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {

        $ubicacion = $this->ubicacionGeografica()->first();

        $address = [];

        if ($ubicacion) {
            $address = $ubicacion->toArray();
            $address['region'] = $ubicacion->region()->first()->descripcion;
            $address['comuna'] = $ubicacion->comuna()->first()->descripcion;
        }

        return [
            'idResponsable' => $this->idResponsable,
            'rut' => $this->rut,
            'name' => $this->name,
            'mail' => $this->mail,
            'idUbicacionGeografica' => (int)$this->idUbicacionGeografica,
            'name_rut' => $this->name . ' / ' . $this->rut,
            'ubicacion' => $address
        ];
    }
}
