<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    //
    public function login(Request $request)
    {
        $this->validateLogin($request);
    
        if (Auth::attempt($request->only('email', 'password'))) {
            $user = Auth::user();
            $role = $user->role;
    
            if (!$role) {
                return response()->json([
                    'message' => 'User does not have an associated role'
                ], 400);
            }
    
            $permissions = \DB::table('entities')
                ->leftJoin('roles_entities_permissions', 'entities.id', '=', 'roles_entities_permissions.entity_id')
                ->where(function ($query) use ($role) {
                    $query->where('roles_entities_permissions.role_id', $role->id)
                        ->orWhereNull('roles_entities_permissions.role_id');
                })
                ->select(
                    'entities.tag as entity_tag',
                    'roles_entities_permissions.role_id',
                    'roles_entities_permissions.show',
                    'roles_entities_permissions.edit',
                    'roles_entities_permissions.create',
                    'roles_entities_permissions.delete'
                )
                ->get();
    
            $entities = \DB::table('entities')->pluck('tag')->toArray();
            $formattedPermissions = [];
    
            foreach ($entities as $entity) {
                $permission = $permissions->firstWhere('entity_tag', $entity);
    
                if ($permission && $permission->role_id === $role->id) {
                    $formattedPermissions[$entity] = [
                        'show' => $permission->show ?? 0,
                        'edit' => $permission->edit ?? 0,
                        'create' => $permission->create ?? 0,
                        'delete' => $permission->delete ?? 0,
                    ];
                } else {
                    // Entidades sin permisos o con role_id no correspondiente
                    $formattedPermissions[$entity] = [];
                }
            }
    
            $expiration = config('sanctum.expiration', null);
            $expires_at = $expiration ? Carbon::now()->addMinutes($expiration) : null;
            $token = $request->user()->createToken($request->name, ['*'], $expires_at);
    
            return response()->json([
                'id_user' => $user->id,
                'token' => $token->plainTextToken,
                'expires_at' => $token->accessToken->expires_at,
                'permissions' => $formattedPermissions,
                'message' => 'Success'
            ]);
        }
    
        return response()->json([
            'message' => 'Unauthorized'
        ], 401);
    }
    

    public function loginByUser(Request $request)
    {
        $this->validateLoginByUser($request);

        $credentials = [
            'name' => $request->user,
            'password' => $request->password
        ];

        if (Auth::attempt($credentials)) {
            $user = Auth::user();
            $role = $user->role;
            if (!$role) {
                return response()->json([
                    'message' => 'User does not have an associated role'
                ], 400);
            }
    
            $permissions = \DB::table('entities')
                ->leftJoin('roles_entities_permissions', 'entities.id', '=', 'roles_entities_permissions.entity_id')
                ->where(function ($query) use ($role) {
                    $query->where('roles_entities_permissions.role_id', $role->id)
                        ->orWhereNull('roles_entities_permissions.role_id');
                })
                ->select(
                    'entities.tag as entity_tag',
                    'roles_entities_permissions.role_id',
                    'roles_entities_permissions.show',
                    'roles_entities_permissions.edit',
                    'roles_entities_permissions.create',
                    'roles_entities_permissions.delete'
                )
                ->get();
    
            $entities = \DB::table('entities')->pluck('tag')->toArray();
            $formattedPermissions = [];
    
            foreach ($entities as $entity) {
                $permission = $permissions->firstWhere('entity_tag', $entity);
    
                if ($permission && $permission->role_id === $role->id) {
                    $formattedPermissions[$entity] = [
                        'show' => $permission->show ?? 0,
                        'edit' => $permission->edit ?? 0,
                        'create' => $permission->create ?? 0,
                        'delete' => $permission->delete ?? 0,
                    ];
                } else {
                    // Entidades sin permisos o con role_id no correspondiente
                    $formattedPermissions[$entity] = [];
                }
            }
            $expiration = config('sanctum.expiration', null);

            $expires_at = $expiration ? Carbon::now()->addMinutes($expiration) : null;

            $token = $request->user()->createToken($request->name, ['*'], $expires_at);

            return response()->json([
                'id_user' => $user->id,
                'token' => $token->plainTextToken,
                'expires_at' => $token->accessToken->expires_at,
                'permissions' => $formattedPermissions,
                'message' => 'Success'
            ]);
        }
    
        return response()->json([
            'message' => 'Unauthorized'
        ], 401);
    }
    

    public function validateLogin(Request $request)
    {
        return $request->validate([
            'email' => 'required|email',
            'password' => 'required',
            'name' => 'required'
        ]);
    }
  

    public function validateLoginByUser(Request $request)
    {
        return $request->validate([
            'user' => 'required|email',
            'password' => 'required',
            'name' => 'required'
        ]);
    }

    public function makePassword(Request $request)
    {

        $request->validate([

            'password' => 'required|min:6',

        ]);

        return response()->json([
            'pass' => \Illuminate\Support\Facades\Hash::make($request->password)
        ], 401);
    }
}
