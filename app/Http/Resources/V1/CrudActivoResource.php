<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Resources\Json\JsonResource;

class CrudActivoResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        $activo = parent::toArray($request);

        $activo['codigo_activo'] = $this->codigo_activo;

        $activo['tipoAlta'] = $this->tipoAltaRelation->descripcion;

        $activo['nombreActivo'] = $this->nombre_activo_origen;

        $activo['marca'] = $this->marcaRelation->descripcion;

        $activo['categoriaN3'] = $this->categoria->descripcionCategoria;

        $activo['responsable'] = $this->responsable->name;

        $activo['organica_n1'] = $this->zona;

        $activo['organica_n2'] = $this->emplazamiento;

        $activo['depreciable'] = $this->depreciableRelation->descripcion;

        $ubicacion = $this->ubicacionGeografica()->first();

        if ($ubicacion) {
            $activo['ubicacion'] = $ubicacion->toArray();
            $activo['ubicacion']['region'] = $ubicacion->region()->first()->descripcion;
            $activo['ubicacion']['comuna'] = $ubicacion->comuna()->first()->descripcion;
        } else {
            $activo['ubicacion'] = [];
        }


        return $activo;
    }
}
