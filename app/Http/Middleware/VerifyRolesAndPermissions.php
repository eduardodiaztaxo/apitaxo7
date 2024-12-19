<?php

namespace App\Http\Middleware;

use App\Models\Auth\Entity;
use App\Models\Auth\Role;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Database\Eloquent\Builder;

class VerifyRolesAndPermissions
{


    /**
     * The Guard implementation.
     *
     * @var Guard
     */
    protected $auth;


    /**
     * Create a new filter instance.
     *
     * @param  Guard  $auth
     * @return void
     */
    public function __construct(Guard $auth)
    {
        $this->auth = $auth;
    }



    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next, string $entity_tag, string $permission)
    {
        if ($this->auth->guest()) {
            return response()->json([
                'message' => 'Unauthorized for this resource'
            ], 401);
        }

        $user = $this->auth->user();

        if (!$user->role_id || empty($user->role_id)) {

            return response()->json([
                'message' => 'Unauthorized for this resource'
            ], 401);
        }

        $role = Role::find($user->role_id);

        $entity = Entity::where('tag', '=', $entity_tag)->first();

        $role_entity_permission = $role->permissions()->where('entity_id', '=', $entity->id)->first();

        if ($role_entity_permission && $role_entity_permission->$permission === 0) {
            return response()->json([
                'message' => 'Unauthorized for this resource'
            ], 401);
        }


        return $next($request);
    }
}
