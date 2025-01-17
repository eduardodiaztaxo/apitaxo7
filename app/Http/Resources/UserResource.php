<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
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
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            "nombre_cliente" => $this->nombre_cliente,
            "role_id" => $this->role_id,
            "role" => $this->role ? $this->role->name : '',
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
