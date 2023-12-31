<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\PostController as PostV1;
use App\Http\Controllers\Api\V2\PostController as PostV2;
use App\Http\Controllers\Api\V1\BajaDocumentoController as BajaV1;
use App\Http\Controllers\Api\V1\RespActivoController as RespActivoV1;
use App\Http\Controllers\Api\V1\RespLocalizacionController as RespLocV1; 
/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
//     return $request->user();
// });

Route::middleware(['auth:sanctum','switch.database'])->prefix('v1')->group(function () {

    Route::apiResource('posts', PostV1::class)->only(['index','show','destroy']);

    Route::apiResource('bajas', BajaV1::class)->only(['index','show','destroy']);

    Route::apiResource('activos/actualizar', RespActivoV1::class)->only(['store']);

    Route::post('activos/actualizar-multiples', [RespActivoV1::class, 'storeMultiple']);

    Route::apiResource('localizaciones/actualizar', RespLocV1::class)->only(['store']);

    Route::post('localizaciones/actualizar-multiples', [RespLocV1::class, 'storeMultiple']);

});



// Route::middleware('auth:sanctum')->prefix('v2')->group(function () {

//     Route::apiResource('posts', PostV2::class)->only(['index','show']);

// });

Route::post('login',[
    App\Http\Controllers\Api\LoginController::class,
    'login'
]);
