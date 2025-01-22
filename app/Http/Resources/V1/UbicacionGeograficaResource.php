<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Models\CiclosEstados;

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

        $estadoDescripcion = CiclosEstados::where('id_estado', $this->estadoGeo)->value('descripcion');

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
            'descEstadoGeo'  => $estadoDescripcion ?? 'Estado no encontrado',
            'zonas_punto'   => $zonas_punto,
            'num_activos'   => $this->activos()->get()->count(),
            'num_activos_cats_by_cycle' => 0,
            'num_cats_by_cycle' => 0,
        ];

        if (isset($this->cycle_id) && $this->cycle_id) {


            $address['num_activos_cats_by_cycle'] = $this->activos_with_cats_by_cycle($this->cycle_id)->count();

            $coll = $this->cats_by_cycle($this->cycle_id);

            $address['num_cats_by_cycle']       = $coll->pluck('categoriaN1')->unique()->count();
            $address['num_subcats_n2_by_cycle'] = $coll->pluck('categoriaN2')->unique()->count();
            $address['num_subcats_n3_by_cycle'] = $coll->pluck('categoriaN3')->unique()->count();
        }



        return $address;
    }
}
