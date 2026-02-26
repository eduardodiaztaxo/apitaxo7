<?php

namespace App\Http\Resources;


use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\DB;

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


                   
        $role = $this->role;

        if (!$role) {
            return response()->json([
                'message' => 'User does not have an associated role'
            ], 400);
        }

            $permissions = DB::connection('mysql_auth')->table('entities')
                ->leftJoin('roles_entities_permissions', 'entities.id', '=', 'roles_entities_permissions.entity_id')
                ->where(function ($query) use ($role) {
                    $query->where('roles_entities_permissions.role_id', $role->id)
                        ->orWhereNull('roles_entities_permissions.role_id');
                })
                ->select(
                    'entities.id',
                    'entities.tag as entity_tag',
                    'roles_entities_permissions.show',
                    'roles_entities_permissions.edit',
                    'roles_entities_permissions.create',
                    'roles_entities_permissions.delete'
                )
                ->get();

            $entities = DB::connection('mysql_auth')->table('entities')->pluck('tag')->toArray();
            $formattedPermissions = [];

            foreach ($entities as $entity) {
                $permission = $permissions->firstWhere('entity_tag', $entity);

                if ($permission) {
                    // Si el ID de la entidad es 4, asignar todos los permisos 
                    if ($permission->id == 4) {
                        $formattedPermissions = [
                            'zona' => ['show' => 1, 'edit' => 1, 'create' => 1, 'delete' => 1],
                            'emplazamiento' => ['show' => 1, 'edit' => 1, 'create' => 1, 'delete' => 1],
                            'bienes_marcas' => ['show' => 1, 'edit' => 1, 'create' => 1, 'delete' => 1],
                            'mover_bienes' => ['show' => 1, 'edit' => 1, 'create' => 1, 'delete' => 1],
                        ];
                        break;
                    } else {
                        $formattedPermissions[$entity] = [
                            'show' => $permission->show ?? 0,
                            'edit' => $permission->edit ?? 0,
                            'create' => $permission->create ?? 0,
                            'delete' => $permission->delete ?? 0,
                        ];
                    }
                }
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
            'signature_register' => $signature_register,
            'permissions' => $formattedPermissions,
        ];
    }
}
