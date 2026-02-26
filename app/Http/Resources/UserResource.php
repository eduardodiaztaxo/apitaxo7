<?php
namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Auth;
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
        $role = $this->role;

        if (!$role) {
            abort(400, 'User does not have an associated role');
        }

        return [
            'id' => $this->id,
            'name' => $this->name,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'email' => $this->email,
            'rut' => $this->rut,
            'nombre_cliente' => $this->nombre_cliente,
            'proyecto_id' => $this->proyecto_id,
            'role_id' => $this->role_id,
            'role' => $role->name,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'signature_register' => $this->hasSignatureRegistered(),
            'permissions' => $this->getUserPermissions($role->id),
        ];
    }

    /**
     * Check if user has a valid signature registered
     */
    private function hasSignatureRegistered(): int
    {
        if (!$this->signature) {
            return 0;
        }

        return preg_match('/^data:image\/png;base64,/', $this->signature) ? 1 : 0;
    }

    /**
     * Get formatted user permissions
     */
    private function getUserPermissions(int $roleId): array
    {
        $permissions = DB::connection('mysql_auth')
            ->table('entities')
            ->leftJoin('roles_entities_permissions', 'entities.id', '=', 'roles_entities_permissions.entity_id')
            ->where(function ($query) use ($roleId) {
                $query->where('roles_entities_permissions.role_id', $roleId)
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

        return $this->formatPermissions($permissions);
    }

    /**
     * Format permissions array
     */
    private function formatPermissions($permissions): array
    {
        $entities = DB::connection('mysql_auth')
            ->table('entities')
            ->pluck('tag')
            ->toArray();

        $formattedPermissions = [];

        foreach ($entities as $entity) {
            $permission = $permissions->firstWhere('entity_tag', $entity);

            if (!$permission) {
                continue;
            }

            // ID 4 tiene permisos completos
            if ($permission->id == 4) {
                return $this->getFullPermissions();
            }

            $formattedPermissions[$entity] = [
                'show' => $permission->show ?? 0,
                'edit' => $permission->edit ?? 0,
                'create' => $permission->create ?? 0,
                'delete' => $permission->delete ?? 0,
            ];
        }

        return $formattedPermissions;
    }

    /**
     * Get full permissions for all entities
     */
    private function getFullPermissions(): array
    {
        return [
            'zona' => ['show' => 1, 'edit' => 1, 'create' => 1, 'delete' => 1],
            'emplazamiento' => ['show' => 1, 'edit' => 1, 'create' => 1, 'delete' => 1],
            'bienes_marcas' => ['show' => 1, 'edit' => 1, 'create' => 1, 'delete' => 1],
            'mover_bienes' => ['show' => 1, 'edit' => 1, 'create' => 1, 'delete' => 1],
        ];
    }
}