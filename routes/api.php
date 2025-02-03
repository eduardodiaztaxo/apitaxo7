<?php

use App\Http\Controllers\Api\TestConnectionController;
use App\Http\Controllers\Api\V1\Auditoria\InventarioConteoController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\PostController as PostV1;
use App\Http\Controllers\Api\V2\PostController as PostV2;
use App\Http\Controllers\Api\V1\BajaDocumentoController as BajaV1;
use App\Http\Controllers\Api\V1\CiclosController;
use App\Http\Controllers\Api\V1\CiclosEmplazamientosController;
use App\Http\Controllers\Api\V1\CiclosUbicacionesController;
use App\Http\Controllers\Api\V1\Comunes\DatosActivosController;
use App\Http\Controllers\Api\V1\CrudActivoController;
use App\Http\Controllers\Api\V1\EmplazamientoController;
use App\Http\Controllers\Api\V1\EmplazamientosActivosController;
use App\Http\Controllers\Api\V1\RespActivoController as RespActivoV1;
use App\Http\Controllers\Api\V1\RespLocalizacionController as RespLocV1;
use App\Http\Controllers\Api\V1\User\UserController;
use App\Http\Controllers\Api\V1\ZonaController;
use App\Http\Controllers\Api\V1\ZonaEmplazamientosController;
use App\Http\Controllers\Api\V1\ZonasActivosController;
use App\Http\Controllers\Api\V1\InventariosController;

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

    Route::post('activos/actualizar-multiples', [RespActivoV1::class, 'storeMultiple_v2']);

    Route::post('activos/actualizar-multiples-v2', [RespActivoV1::class, 'storeMultiple_v2']);

    Route::apiResource('localizaciones/actualizar', RespLocV1::class)->only(['store']);

    Route::post('localizaciones/actualizar-multiples', [RespLocV1::class, 'storeMultiple']);

    Route::post('generate-encrypt-password', [
        App\Http\Controllers\Api\LoginController::class,
        'makePassword'
    ]);

    Route::get('activos/etiqueta/{etiqueta}', [CrudActivoController::class, 'showByEtiqueta']);

    Route::put('activos/upload-image/etiqueta/{etiqueta}', [CrudActivoController::class, 'uploadImageByEtiqueta']);

    Route::put('activos/update/etiqueta/{etiqueta}', [CrudActivoController::class, 'update']);

    Route::get('marcas', [DatosActivosController::class, 'marcas']);

    Route::get('responsables', [DatosActivosController::class, 'responsables']);

    Route::get('estados-bienes', [DatosActivosController::class, 'estadosBienes']);

    Route::get('grupo/{ciclo}', [DatosActivosController::class, 'grupo']);

    Route::get('familia/{codigo_grupo}', [DatosActivosController::class, 'familia']);

    Route::get('bienes_marcas/{id_familia}', [DatosActivosController::class, 'bienes_Marcas']);

    Route::get('colores', [DatosActivosController::class, 'indiceColores']);

    Route::get('estados-operacionales', [DatosActivosController::class, 'estadosOperacional']);

    Route::get('tipos-trabajo', [DatosActivosController::class, 'tipoTrabajo']);

    Route::get('cargas-trabajo', [DatosActivosController::class, 'cargaTrabajo']);

    Route::get('condiciones-ambientales', [DatosActivosController::class, 'condicionAmbiental']);

    Route::get('estados-conservacion', [DatosActivosController::class, 'estadoConservacion']);

    Route::get('materiales/{id_familia}', [DatosActivosController::class, 'material']);

    Route::get('formas/{id_familia}', [DatosActivosController::class, 'forma']);

    Route::post('show-bienes', [DatosActivosController::class, 'showBienes']);

    Route::post('show-marcas', [DatosActivosController::class, 'showMarcas']);

    Route::post('show-inventario', [InventariosController::class, 'showinventario']);

    Route::get('localizacion/{etiqueta}', [CrudActivoController::class, 'localizacion']);

    Route::get('marcas-disponibles/{etiqueta}', [CrudActivoController::class, 'marcasDisponibles']);

    Route::get('ciclos', [CiclosController::class, 'index']);

    Route::get('ciclos/{ciclo}', [CiclosController::class, 'show']);

    Route::get('ciclos-by-user', [CiclosController::class, 'indexByUser']);

    Route::get('ciclos/{ciclo}/puntos', [CiclosUbicacionesController::class, 'show']);

    Route::get('ciclos/{ciclo}/puntos-and-zones-with-cats', [CiclosUbicacionesController::class, 'showByCycleCats']);

    //Route::get('zones/{zona}/emplazamientos', [ZonaEmplazamientosController::class, 'show']);

    Route::get('zones/{zona}/emplazamientos', [ZonaEmplazamientosController::class, 'show'])->middleware('roles.permissions:emplazamiento,edit');

    Route::get('ciclos/{ciclo}/zones/{zona}/emplazamientos', [ZonaEmplazamientosController::class, 'showByCycleCats']);

    Route::get('ciclos/{ciclo}/zones/{zona}', [ZonaController::class, 'showByCycleCats']);

    Route::get('zones/{zona}', [ZonaController::class, 'show'])->name('zone.show');

    Route::post('zones', [ZonaController::class, 'store']);

    Route::get('ciclos/{ciclo}/zones/{zona}/activos/etiquetas', [ZonasActivosController::class, 'showOnlyLabelsByCycleCats']);

    Route::get('emplazamientos/{emplazamiento}/activos', [EmplazamientosActivosController::class, 'show']);

    Route::get('emplazamientos/{emplazamiento}/activos/etiquetas', [EmplazamientosActivosController::class, 'showOnlyLabels']);

    Route::get('puntos/{punto}/emplazamientos/{emplazamiento_code}/exists', [EmplazamientoController::class, 'existsEmplazamiento']);

    Route::get('ciclos/{ciclo}/emplazamientos/{emplazamiento}/activos/etiquetas', [EmplazamientosActivosController::class, 'showOnlyLabelsByCycleCats']);

    Route::get('emplazamientos/{emplazamiento}', [EmplazamientoController::class, 'show']);

    Route::put('emplazamientos/{id}', [EmplazamientoController::class, 'update']);

    Route::post('emplazamientos/create', [EmplazamientoController::class, 'create']);

    Route::post('emplazamientos', [EmplazamientoController::class, 'store']);

    Route::get('ciclos/{ciclo}/emplazamientos/{emplazamiento}', [CiclosEmplazamientosController::class, 'show']);

    Route::post('auditorias/procesar-conteo', [InventarioConteoController::class, 'processConteo']);

    Route::post('auditorias/procesar-conteo-por-emplazamiento', [InventarioConteoController::class, 'processConteoByEmplazamientoMultipleUsers']);

    Route::post('auditorias/procesar-conteo-por-zona', [InventarioConteoController::class, 'processConteoByZonaMultipleUsers']);

    Route::get('auditorias/ciclos/{ciclos}/conteo-por-emplazamiento/{emplazamiento}', [InventarioConteoController::class, 'showConteoByEmplazamiento']);

    Route::get('auditorias/ciclos/{ciclos}/conteo-por-zona/{zona}', [InventarioConteoController::class, 'showConteoByZone']);

    Route::post('auditorias/reset-conteo-emplazamiento', [InventarioConteoController::class, 'resetConteoByEmplazamiento']);

    Route::post('auditorias/reset-conteo-zona', [InventarioConteoController::class, 'resetConteoByZona']);

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
