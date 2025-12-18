<?php

namespace App\Http\Resources\V2;

use Illuminate\Http\Resources\Json\JsonResource;

class EmplazamientoNivel2LiteResource extends JsonResource
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
            'id' => $this->idUbicacionN2,
            'codigo' => $this->codigo,
            'codigoUbicacion' => $this->codigoUbicacion,
            'nombre' => $this->descripcionUbicacion,
            'idAgenda' => $this->idAgenda,
            'idUbicacionN2' => $this->idUbicacionN2,
            'detalle' => 'Detalle Emplazamiento (N2)',
            'num_nivel' => 'N2',
            'next_level' => 'N3',
            'newApp' => $this->newApp,
            'modo' => $this->modo,
            'habilitadoNivel3' => 1,
            'ciclo_auditoria' => $this->ciclo_auditoria,

            'id_ciclo' => $this->cycle_id,

        ];







        return $emplazamiento;
    }
}
