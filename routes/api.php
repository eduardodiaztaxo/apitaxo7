<?php

use App\Http\Controllers\Api\TestConnectionController;
use App\Http\Controllers\Api\V1\Auditoria\InventarioConteoController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\PostController as PostV1;
use App\Http\Controllers\Api\V2\PostController as PostV2;
use App\Http\Controllers\Api\V1\BajaDocumentoController as BajaV1;
use App\Http\Controllers\Api\V1\CiclosController;
use App\Http\Controllers\Api\V1\CiclosUbicacionesController;
use App\Http\Controllers\Api\V1\CrudActivoController;
use App\Http\Controllers\Api\V1\EmplazamientoController;
use App\Http\Controllers\Api\V1\EmplazamientosActivosController;
use App\Http\Controllers\Api\V1\RespActivoController as RespActivoV1;
use App\Http\Controllers\Api\V1\RespLocalizacionController as RespLocV1;
use App\Http\Controllers\Api\V1\User\UserController;
use App\Http\Controllers\Api\V1\ZonaEmplazamientosController;

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

Route::middleware(['auth:sanctum', 'switch.database'])->prefix('v1')->group(function () {


    Route::get('test/connection', [TestConnectionController::class, 'pin']);

    Route::apiResource('posts', PostV1::class)->only(['index', 'show', 'destroy']);

    Route::apiResource('bajas', BajaV1::class)->only(['index', 'show', 'destroy']);

    Route::apiResource('activos/actualizar', RespActivoV1::class)->only(['store']);

    Route::post('activos/actualizar-multiples', [RespActivoV1::class, 'storeMultiple']);

    Route::post('activos/actualizar-multiples-v2', [RespActivoV1::class, 'storeMultiple_v2']);

    Route::apiResource('localizaciones/actualizar', RespLocV1::class)->only(['store']);

    Route::post('localizaciones/actualizar-multiples', [RespLocV1::class, 'storeMultiple']);

    Route::post('generate-encrypt-password', [
        App\Http\Controllers\Api\LoginController::class,
        'makePassword'
    ]);

    Route::get('activos/etiqueta/{etiqueta}', [CrudActivoController::class, 'showByEtiqueta']);

    Route::put('activos/upload-image/etiqueta/{etiqueta}', [CrudActivoController::class, 'uploadImageByEtiqueta']);



    Route::get('ciclos', [CiclosController::class, 'index']);

    Route::get('ciclos-by-user', [CiclosController::class, 'indexByUser']);


    Route::get('ciclos/{ciclo}/puntos', [CiclosUbicacionesController::class, 'show']);

    Route::get('ciclos/{ciclo}/puntos-and-zones-with-cats', [CiclosUbicacionesController::class, 'showByCycleCats']);


    Route::get('zones/{zona}/emplazamientos', [ZonaEmplazamientosController::class, 'show']);

    Route::get('ciclos/{ciclo}/zones/{zona}/emplazamientos', [ZonaEmplazamientosController::class, 'showByCycleCats']);

    Route::get('emplazamientos/{emplazamiento}/activos', [EmplazamientosActivosController::class, 'show']);

    Route::get('emplazamientos/{emplazamiento}/activos/etiquetas', [EmplazamientosActivosController::class, 'showOnlyLabels']);

    Route::get('puntos/{punto}/emplazamientos/{emplazamiento_code}/exists', [EmplazamientoController::class, 'existsEmplazamiento']);

    Route::get('ciclos/{ciclo}/emplazamientos/{emplazamiento}/activos/etiquetas', [EmplazamientosActivosController::class, 'showOnlyLabelsByCycleCats']);


    Route::get('emplazamientos/{emplazamiento}', [EmplazamientoController::class, 'show']);


    Route::post('auditorias/procesar-conteo', [InventarioConteoController::class, 'processConteo']);

    Route::post('auditorias/procesar-conteo-por-emplazamiento', [InventarioConteoController::class, 'processConteoByEmplazamiento']);






    //
});


Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {

    Route::get('my-user-info', [UserController::class, 'show']);
});



// Route::middleware('auth:sanctum')->prefix('v2')->group(function () {

//     Route::apiResource('posts', PostV2::class)->only(['index','show']);

// });

Route::post('login', [
    App\Http\Controllers\Api\LoginController::class,
    'login'
]);


Route::post('login-by-user', [
    App\Http\Controllers\Api\LoginController::class,
    'loginByUser'
]);

Route::post('recovery', [
    App\Http\Controllers\Api\ForgotPasswordController::class,
    'sendResetLink'
]);
