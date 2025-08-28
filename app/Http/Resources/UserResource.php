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



        $signature_register = 0;

        if ($this->signature && preg_match('/^data:image\/png;base64,/', $this->signature)) {
            $signature_register = 1;
        }

        return [
            'id' => $this->id,
            'name' => $this->name,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'email' => $this->email,
            'rut'   => $this->rut,
            "nombre_cliente" => $this->nombre_cliente,
            "proyecto_id" => $this->proyecto_id,
            "role_id" => $this->role_id,
            "role" => $this->role ? $this->role->name : '',
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'signature_register' => $signature_register
        ];
    }
}
