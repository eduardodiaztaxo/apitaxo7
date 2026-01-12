<?php

namespace App\Http\Resources\V2;

use Illuminate\Http\Resources\Json\JsonResource;

class EmplazamientoNivel3LiteResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {


        $emplazamiento = [
            'ciclo' => $this->cycle_id,

            'id' => $this->idUbicacionN3,
            'codigo' => $this->codigo,
            'codigoUbicacion' => $this->codigoUbicacion,
            'nombre' => $this->descripcionUbicacion,
            'idAgenda' => $this->idAgenda,
            'idUbicacionN3' => $this->idUbicacionN3,
            'idUbicacionN2' => $this->idUbicacionN3,
            'detalle' => 'Detalle Emplazamiento (N3)',
            'num_nivel' => 'N3',
            'next_level' => '',
            'newApp' => $this->newApp,
            'modo' => $this->modo,
            'habilitadoNivel3' => 0,

            'id_ciclo' => $this->cycle_id,

        ];


        return $emplazamiento;
    }
}
