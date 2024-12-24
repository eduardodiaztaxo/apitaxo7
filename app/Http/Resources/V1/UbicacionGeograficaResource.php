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
        if (
            isset($this->zonas_cats) && is_array($this->zonas_cats) &&
            isset($this->cycle_id) && $this->cycle_id
        ) {

            $zones = $this->zonasPunto()->whereIn('codigoUbicacion', $this->zonas_cats)->get();

            foreach ($zones as $zone) {
                $zone->cycle_id = $this->cycle_id;
            }

            $zonas_punto = ZonaPuntoResource::collection(
                $zones
            );
        } else if (isset($this->requireZonas) && $this->requireZonas) {

            $zones = $this->zonasPunto()->get();

            foreach ($zones as $zone) {
                $zone->cycle_id = $this->cycle_id;
            }
            $zonas_punto = ZonaPuntoResource::collection($zones);
        } else {
            $zonas_punto = [];
        }

        $address = [
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
            'num_activos'   => $this->activos()->get()->count(),
            'num_activos_cats_by_cycle' => 0,
            'num_cats_by_cycle' => 0,
        ];

        if (isset($this->cycle_id) && $this->cycle_id) {
            $address['num_activos_cats_by_cycle'] = $this->activos_with_cats_by_cycle($this->cycle_id)->count();
            $address['num_cats_by_cycle'] = $this->cats_by_cycle($this->cycle_id)->count();
        }



        return $address;
    }
}
