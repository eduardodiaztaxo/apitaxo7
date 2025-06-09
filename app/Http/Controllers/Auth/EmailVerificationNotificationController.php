<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Providers\RouteServiceProvider;
use Illuminate\Http\Request;

class EmailVerificationNotificationController extends Controller
{
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
            'username' => ['required', 'string', 'max:255'],
        ]);

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
}
