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

        $activosCollection = $this->activos()->get();

        $emplazamiento = [
            'id' => $this->idUbicacionN2,
            'codigo' => $this->codigo,
            'codigoUbicacion' => $this->codigoUbicacion,
            'nombre' => $this->descripcionUbicacion,
            'idAgenda' => $this->idAgenda,
            'idUbicacionN2' => $this->idUbicacionN2,
            'num_activos'   => $activosCollection->count(),
            'num_activos_cats_by_cycle' => 0,
            'num_categorias' => $this->activos()->select('categoriaN3')->groupBy('categoriaN3')->get()->count(),
            'zone_address' => ZonaPuntoResource::make($this->zonaPunto()->first())
        ];

        if (isset($this->requirePunto) && $this->requirePunto) {
            $emplazamiento['ubicacionPunto'] = UbicacionGeograficaResource::make($this->ubicacionPunto()->first());
        }

        if (isset($this->requireActivos) && $this->requireActivos) {
            $emplazamiento['activos'] = CrudActivoResource::collection($activosCollection);
        }



        if (isset($this->cycle_id) && $this->cycle_id) {
            $emplazamiento['num_activos_cats_by_cycle'] = $this->activos_with_cats_by_cycle($this->cycle_id)->count();
        }

        return $emplazamiento;
    }
}
