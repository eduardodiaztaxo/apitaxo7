<?php

namespace App\Http\Resources\Maps;

use App\Models\Inventario;
use Illuminate\Http\Resources\Json\JsonResource;

class MapMarkerAssetResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        $marker = parent::toArray($request);

        

        $category = $this->getCategory;


        if ($category) {

            $secondary_color = $marker['fix_quality'] ? $category->secondary_color : '#ff0000';

            $marker['primary_icon'] = do_icon_marker_map($category->marker_icon, $category->marker_pin, $category->primary_color);
            $marker['secondary_icon'] = do_icon_marker_map($category->marker_icon, $category->marker_pin, $secondary_color);
        }
        return $marker;
    }
}
