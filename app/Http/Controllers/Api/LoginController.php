<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Auth\RefreshToken;
use Illuminate\Http\Request;

use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

use Illuminate\Support\Str;

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

            $permissions = DB::table('entities')
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


            $entities = DB::table('entities')->pluck('tag')->toArray();
            $formattedPermissions = [];

            foreach ($entities as $entity) {
                $permission = $permissions->firstWhere('entity_tag', $entity);

                if ($permission) {
                    // Si el ID de la entidad es 4, asignar todos los permisos 
                    if ($permission->id == 4) {
                        $formattedPermissions = [
                            'zona' => ['show' => 1, 'edit' => 1, 'create' => 1, 'delete' => 1],
                            'emplazamiento' => ['show' => 1, 'edit' => 1, 'create' => 1, 'delete' => 1],
                            'bien' => ['show' => 1, 'edit' => 1, 'create' => 1, 'delete' => 1],
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


            $token = $this->createAccessToken($request->user());

            $refreshToken = $this->createRefreshToken($user);

            return response()->json([
                'id_user' => $user->id,
                'proyecto_id' => $user->proyecto_id,
                'token' => $token->plainTextToken,
                'refresh_token' => $refreshToken['rt_string'],
                'refresh_expires_at' => $refreshToken['rt_model']->expires_at,
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

            $permissions = DB::table('entities')
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

            $entities = DB::table('entities')->pluck('tag')->toArray();
            $formattedPermissions = [];

            foreach ($entities as $entity) {
                $permission = $permissions->firstWhere('entity_tag', $entity);

                if ($permission) {
                    // Si el ID de la entidad es 4, asignar todos los permisos 
                    if ($permission->id == 4) {
                        $formattedPermissions = [
                            'zona' => ['show' => 1, 'edit' => 1, 'create' => 1, 'delete' => 1],
                            'emplazamiento' => ['show' => 1, 'edit' => 1, 'create' => 1, 'delete' => 1],
                            'bien' => ['show' => 1, 'edit' => 1, 'create' => 1, 'delete' => 1],
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


            $token = $this->createAccessToken($request->user());

            $name = DB::table('users')
                ->where('id', $user->id)
                ->pluck(DB::raw("CONCAT(first_name, ' ', last_name)"))
                ->first();

            $refreshToken = $this->createRefreshToken($user);

            return response()->json([
                'id_user' => $user->id,
                'proyecto_id' => $user->proyecto_id,
                'name' => $name,
                'User' => $user->name,
                'email' => $user->email,
                'token' => $token->plainTextToken,
                'refresh_token' => $refreshToken['rt_string'],
                'refresh_expires_at' => $refreshToken['rt_model']->expires_at,
                'expires_at' => $token->accessToken->expires_at,
                'permissions' => $formattedPermissions,
                'message' => 'Success'
            ]);
        }

        return response()->json([
            'message' => 'Unauthorized'
        ], 401);
    }

    public function refreshToken(Request $request)
    {
        $request->validate([
            'token' => 'required',
            'refresh_token' => 'required',
        ]);

        $hashedToken = RefreshToken::hashToken($request->refresh_token);

        $refreshToken = RefreshToken::where('token', $hashedToken)->first();

        if (!$refreshToken || $refreshToken->isExpired()) {
            return response()->json(['message' => 'Invalid or expired refresh token'], 401);
        }

        $user = $refreshToken->user;

        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        $secondPart = explode('|', $request->token);
        if (count($secondPart) !== 2) {
            return response()->json(['message' => 'Invalid access token format'], 401);
        }
        // Validate the access token
        $tokenId = $secondPart[0];
        $tokenText = $secondPart[1];

        $oldAccessToken = $user->tokens()->where('token', User::hashToken($tokenText))->first();

        // Check if the provided access token is valid
        if (!$oldAccessToken || $oldAccessToken->expires_at > $refreshToken->expires_at) {
            return response()->json(['message' => 'Invalid access token'], 401);
        }

        // Revoke old refresh token
        $refreshToken->delete();
        // Revoke old access token
        $oldAccessToken->delete();

        // Issue new access + refresh tokens
        $accessToken = $this->createAccessToken($user);


        $newRefreshTokenString = $this->createRefreshToken($user);

        return response()->json([
            'status' => 'OK',
            'message' => 'Tokens refreshed successfully',
            'access_token' => $accessToken->plainTextToken,
            'refresh_token' => $newRefreshTokenString,
            'expires_at' => $accessToken->accessToken->expires_at,
        ]);
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

    private function createAccessToken(User $user)
    {
        // Access Token (short-lived)
        $expiration = config('sanctum.expiration', null);
        $expires_at = $expiration ? Carbon::now()->addMinutes($expiration) : null;
        return $user->createToken('access_token', ['*'], $expires_at);
    }

    private function createRefreshToken(User $user)
    {
        // Refresh Token (long-lived)
        $refreshTokenString = Str::random(64);

        $expiration = config('sanctum.refresh_expiration', null);
       
        $expires_at = $expiration ? Carbon::now()->addMinutes($expiration) : null;

        $rt = RefreshToken::create([
            'user_id' => $user->id,
            'token' => RefreshToken::hashToken($refreshTokenString),
            'expires_at' => $expires_at,
        ]);

        return [
            'rt_model' => $rt,
            'rt_string' => $refreshTokenString
        ];
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

    public function logout(Request $request)
    {

        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'status' => 'OK',
            'message' => 'Logged out successfully',

        ], 200);
    }

    public function pin(Request $request)
    {

        return response()->json([
            'status' => 'success',
            'message' => 'Connected and Authorized!'
        ], 200);
    }
}
