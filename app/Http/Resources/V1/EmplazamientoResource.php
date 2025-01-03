<?php

namespace App\Http\Resources\V1;

use App\Http\Resources\V1\CrudActivoLiteResource;
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

        $activosCollection = $this->activos()->select(
            'etiqueta',
            'categoriaN3',
            'nombreActivo',
            'idIndice',
            'foto4'
        )->get();

        $emplazamiento = [
            'id' => $this->idUbicacionN2,
            'codigo' => $this->codigo,
            'codigoUbicacion' => $this->codigoUbicacion,
            'nombre' => $this->descripcionUbicacion,
            'idAgenda' => $this->idAgenda,
            'idUbicacionN2' => $this->idUbicacionN2,
            'num_activos'   => $activosCollection->count(),
            'num_activos_cats_by_cycle' => 0,
            'ciclo_auditoria' => $this->ciclo_auditoria,
            'num_categorias' => $this->activos()->select('categoriaN3')->groupBy('categoriaN3')->get()->count(),
            'zone_address' => ZonaPuntoResource::make($this->zonaPunto()->first())
        ];

        if (isset($this->requirePunto) && $this->requirePunto) {
            $emplazamiento['ubicacionPunto'] = UbicacionGeograficaResource::make($this->ubicacionPunto()->first());
        }

        if (isset($this->requireActivos) && $this->requireActivos) {
            if (isset($this->cycle_id) && $this->cycle_id) {
                $emplazamiento['activos'] = CrudActivoLiteResource::collection($this->activos_with_cats_by_cycle($this->cycle_id)->get());
            } else {
                $emplazamiento['activos'] = CrudActivoLiteResource::collection($activosCollection);
            }
        }



        if (isset($this->cycle_id) && $this->cycle_id) {
            $emplazamiento['num_activos_cats_by_cycle'] = isset($emplazamiento['activos']) ? count($emplazamiento['activos']) : $this->activos_with_cats_by_cycle($this->cycle_id)->count();
        }

        return $emplazamiento;
    }
}
