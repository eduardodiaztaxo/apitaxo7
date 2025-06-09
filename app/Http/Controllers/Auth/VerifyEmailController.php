<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\SecScUser;
use App\Models\User;
use App\Providers\RouteServiceProvider;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class VerifyEmailController extends Controller
{
    /**
     * Mark the authenticated user's email address as verified.
     *
     * @param  \Illuminate\Foundation\Auth\EmailVerificationRequest  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function __invoke(EmailVerificationRequest $request)
    {
        if ($request->user()->hasVerifiedEmail()) {
            return view('auth.verify-success');
            //return redirect()->intended(RouteServiceProvider::HOME . '?verified=1');
        }

        if ($request->user()->markEmailAsVerified()) {
            event(new Verified($request->user()));
        }

        //Update email_verified_at in sec_users
        $conn_field = $request->user()->conn_field;

        $login = $request->user()->name;

        $secScUser = SecScUser::on($conn_field)->find($login);

        $secScUser->email_verified_at = date('Y-m-d H:i:s');

        $secScUser->save();



        return view('auth.verify-success', [
            'callback' => $request->callback,
        ]);

        //return redirect()->intended(RouteServiceProvider::HOME . '?verified=1');
    }

    /**
     * Mark the authenticated user's email address as verified.
     *
     * @param  \Illuminate\Foundation\Auth\EmailVerificationRequest  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function __preinvoke(Request $request)
    {
        if ($request->route('id') && $user = User::find($request->route('id'))) {

            Auth::login($user);
            $request = EmailVerificationRequest::createFrom($request);
            return $this->__invoke($request);
        }


        return redirect()->intended(RouteServiceProvider::HOME . '?verified=1');
    }
}
