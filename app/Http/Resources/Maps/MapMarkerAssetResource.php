<?php

namespace App\Http\Resources\Maps;

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
            $marker['primary_icon'] = do_icon_marker_map($category->marker_icon, $category->marker_pin, $category->primary_color);
            $marker['secondary_icon'] = do_icon_marker_map($category->marker_icon, $category->marker_pin, $category->secondary_color);
        }
        return $marker;
    }
}
