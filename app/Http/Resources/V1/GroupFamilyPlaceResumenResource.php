<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Resources\Json\JsonResource;

class GroupFamilyPlaceResumenResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {

        $family_place_resumen = $this->resource;

        if ($family_place_resumen->count() === 0) {
            return [];
        }

        return [
            'place_code' => $family_place_resumen->first()->codigoUbicacion,
            'place_level' => $family_place_resumen->first()->place_level,

            'is_sub' => $family_place_resumen->first()->isSub,
            //assets with match family
            'total' => $family_place_resumen->sum('total'),
            'total_place' => 0,
            'group_family_resumen' => GroupFamilyResumenResource::collection($family_place_resumen),
            'child_resumen' => null
        ];
    }
}
