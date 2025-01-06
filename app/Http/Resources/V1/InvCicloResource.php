<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Resources\Json\JsonResource;

class InvCicloResource extends JsonResource
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
            'idCiclo'       => $this->idCiclo,
            'status'        => $this->estadoCiclo,
            'status_name'   => $this->estadoCiclo === 1 ? 'Proceso' : ($this->estadoCiclo === 2 ? 'Cerrado' : 'Abierto'),
            'title'         => $this->descripcion,
            'date'          => $this->fechaInicio,
            'date_end'      => $this->fechaTermino,
            'assets_cycle'  => $this->activos_with_cats()->count()
        ];
    }
}
