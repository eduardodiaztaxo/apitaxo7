<?php

namespace App\Http\Resources\V2;


use Illuminate\Http\Resources\Json\JsonResource;


class CrudActivoResource extends JsonResource
{




    /**
     * Create a new resource instance.
     *
     * @param  mixed  $resource
     * @return void
     */
    public function __construct($resource)
    {

    }

    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        $activo = parent::toArray($request);

        return $activo;
    }
}
