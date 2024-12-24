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

            $expiration = config('sanctum.expiration', null);

            $expires_at = $expiration ? Carbon::now()->addMinutes($expiration) : null;

            $token = $request->user()->createToken($request->name, ['*'], $expires_at);

            return response()->json([
                'token' => $token->plainTextToken,
                'expires_at' => $token->accessToken->expires_at,
                'permissions' => [
                    'zona' => [
                        'show' => 1,
                        'edit' => 1,
                        'create' => 1,
                        'delete' => 1,
                    ],
                    'emplazamiento' => [
                        'show' => 1,
                        'edit' => 1,
                        'create' => 1,
                        'delete' => 1,
                    ],
                ],
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

            $expiration = config('sanctum.expiration', null);

            $expires_at = $expiration ? Carbon::now()->addMinutes($expiration) : null;

            $token = $request->user()->createToken($request->name, ['*'], $expires_at);

            return response()->json([
                'token' => $token->plainTextToken,
                'expires_at' => $token->accessToken->expires_at,
                'permissions' => [
                    'zona' => [
                        'show' => 1,
                        'edit' => 1,
                        'create' => 1,
                        'delete' => 1,
                    ],
                    'emplazamiento' => [
                        'show' => 1,
                        'edit' => 1,
                        'create' => 1,
                        'delete' => 1,
                    ],
                ],
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
