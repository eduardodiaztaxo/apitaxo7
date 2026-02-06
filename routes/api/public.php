<?php

use Illuminate\Support\Facades\Route;

Route::get('v1/pin-internet-status', function () {
    return response()->json(['message' => 'Pin is working']);
});

Route::post('login', [
    App\Http\Controllers\Api\LoginController::class,
    'login'
]);


Route::post('login-by-user', [
    App\Http\Controllers\Api\LoginController::class,
    'loginByUser'
]);

Route::post('refresh-token', [
    App\Http\Controllers\Api\LoginController::class,
    'refreshToken'
]);

Route::post('recovery', [
    App\Http\Controllers\Api\ForgotPasswordController::class,
    'sendResetLink'
]);

Route::post('encrypt-pass-word', [
    App\Http\Controllers\Auth\NewPasswordController::class,
    'hashPass'
]);

Route::post('send-verification-mail', [
    App\Http\Controllers\Auth\EmailVerificationNotificationController::class,
    'sendMailVerificationByUsername'
]);
Route::post('debug-token', [
    App\Http\Controllers\Auth\EmailVerificationNotificationController::class,
    'debugToken'
]);

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {

    Route::get('my-user-info', [App\Http\Controllers\Api\V1\User\UserController::class, 'show']);

    Route::post('users/register-signature', [App\Http\Controllers\Api\V1\User\UserController::class, 'registerSignature']);

    Route::post('logout', [
        App\Http\Controllers\Api\LoginController::class,
        'logout'
    ]);
});
