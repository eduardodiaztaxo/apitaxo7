<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Resources\Json\JsonResource;

class GroupFamilyResumenResource extends JsonResource
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
            'id_grupo' => $this->id_grupo,
            'id_familia' => $this->id_familia,
            'family_description' => $this->descripcion_familia,
            'total' => $this->total
        ];
    }
}
