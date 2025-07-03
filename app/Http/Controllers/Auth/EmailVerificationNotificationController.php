<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Providers\RouteServiceProvider;
use Illuminate\Http\Request;
use App\Services\TokenEncodeDecodeService;

class EmailVerificationNotificationController extends Controller
{
    protected $tokenManager;

    public function __construct()
    {

        $seed = env('TOKEN_SEED');
        $this->tokenManager = new TokenEncodeDecodeService($seed);
    }
    /**
     * Send a new email verification notification.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->intended(RouteServiceProvider::HOME);
        }

        $request->user()->sendEmailVerificationNotification();

        return back()->with('status', 'verification-link-sent');
    }


    /**
     * Send a new email verification notification by username.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function sendMailVerificationByUsername(Request $request)
    {


        $request->validate([
            'username'  => ['required', 'string', 'max:255'],
            'callback'  => ['nullable', 'url'],
            'token'     => ['required', 'string'],
        ]);



        $data = $this->tokenManager->decode($request->token);



        if (!$data || !isset($data['exp']) || time() > $data['exp']) {
            return response()->json([
                'status' => 'error',
                'message' => 'Token inválido o expirado',
                'data' => $data
            ], 401);
        }

        $user = User::where('name', $request->username)->first();

        if (!$user) {
            return response()->json(['status' => 'error', 'message' => 'User not found'], 404);
        }

        if ($user->hasVerifiedEmail()) {
            return response()->json(['status' => 'error', 'message' => 'El usuario ya ha sido verificado'], 409);
        }


        $callback = $request->callback ?? null;

        $user->sendEmailVerificationNotification($callback);

        return response()->json(['status' => 'OK', 'message' => 'the verification email has been sent successfully']);
    }

    public function debugToken(Request $request)
    {
        $data = $this->tokenManager->decode($request->token);

        if (!$data) {
            return response()->json([
                'status' => 'error',
                'message' => 'Token inválido o malformado',
                'debug' => [
                    'token' => $request->token,
                    'seed' => substr($this->tokenManager->getSeed(), 0, 32), // Añade esto si implementás getSeed()
                    'decoded_base64' => base64_encode(base64_decode($request->token))

                ]
            ]);
        }

        return response()->json(['status' => 'ok', 'data' => $data]);
    }
}
