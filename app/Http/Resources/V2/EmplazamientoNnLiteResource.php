<?php

namespace App\Http\Resources\V2;

use Illuminate\Http\Resources\Json\JsonResource;

class EmplazamientoNnLiteResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {

        $level = $this->getSubnivel();

        $idProperty = 'idUbicacionN' . $level;

        $emplazamiento = [




            'id' => $this->{$idProperty},
            'codigo' => $this->codigo,
            'codigoUbicacion' => $this->codigoUbicacion,
            'nombre' => $this->descripcionUbicacion,
            'idAgenda' => $this->idAgenda,
            $idProperty => $this->{$idProperty},
            'detalle' => 'Detalle Emplazamiento (N' . $level . ')',
            'num_nivel' => 'N3',
            'next_level' => '',
            'newApp' => $this->newApp,
            'modo' => $this->modo,
            'habilitadoNivel3' => 0,



        ];


        return $emplazamiento;
    }
}
