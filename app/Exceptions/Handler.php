<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that are not reported.
     *
     * @var array
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed for validation exceptions.
     *
     * @var array
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     *
     * @return void
     */
    public function register()
    {
        $this->reportable(function (Throwable $e) {
            //
        });
    }


    /**
     * Convert an authentication exception into a response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Illuminate\Auth\AuthenticationException  $exception
     * @return \Symfony\Component\HttpFoundation\Response
     */
    protected function unauthenticated($request, \Illuminate\Auth\AuthenticationException $exception)
    {


        $json_response = ['message' => $exception->getMessage(), 'type' => 'unauthenticated'];

        if (in_array('sanctum', $exception->guards())) {

            $token = \App\Models\Sanctum\PersonalAccessToken::getTokenFromRequest($request);

            if ($token && !empty($token)) {

                $accessToken = \App\Models\Sanctum\PersonalAccessToken::getAccessToken($token);

                $isValid = \App\Models\Sanctum\PersonalAccessToken::isValidAccessToken($accessToken);

                if (!$isValid) {
                    $json_response['type'] = 'invalid_token';
                }
            }
        }

        return $this->shouldReturnJson($request, $exception)
            ? response()->json($json_response, 401)
            : redirect()->guest($exception->redirectTo() ?? route('login'));
    }
}
