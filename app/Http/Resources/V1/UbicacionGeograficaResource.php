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
        if (isset($this->zonas_cats) && is_array($this->zonas_cats)) {
            $zonas_punto = ZonaPuntoResource::collection(
                $this->zonasPunto()->whereIn('codigoUbicacion', $this->zonas_cats)->get()
            );
        } else
            $zonas_punto = ZonaPuntoResource::collection($this->zonasPunto()->get());

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
            'zonas_punto'   => $zonas_punto,
            'num_activos'   => $this->activos()->get()->count()
        ];
    }
}
