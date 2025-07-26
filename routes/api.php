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
use App\Http\Controllers\Api\V1\Maps\MapMarkerController;
use App\Http\Controllers\Api\V1\Maps\MapPolygonController;
use App\Http\Controllers\Api\V1\Responsible\AssignResponsibleController;
use App\Http\Controllers\Api\V1\ResponsibleController;
use App\Http\Controllers\Api\V1\UbicacionesActivosController;

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

    Route::get('pin', [App\Http\Controllers\Api\LoginController::class, 'pin']);


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

    Route::post('activos/etiquetas', [CrudActivoController::class, 'showByEtiquetas']);

    Route::post('activos/not-responsibles/etiquetas', [CrudActivoController::class, 'showByEtiquetasWithoutResponsibles']);

    Route::put('activos/upload-image/etiqueta/{etiqueta}', [CrudActivoController::class, 'uploadImageByEtiqueta']);

    Route::put('activos/update/etiqueta/{etiqueta}', [CrudActivoController::class, 'update']);

    Route::get('marcas', [DatosActivosController::class, 'marcas']);

    Route::get('responsables', [DatosActivosController::class, 'responsables']);



    Route::get('responsibles', [ResponsibleController::class, 'showAll']);

    Route::get('responsibles/{id}', [ResponsibleController::class, 'show']);

    Route::post('responsibles', [ResponsibleController::class, 'store']);

    Route::put('responsibles/{id}', [ResponsibleController::class, 'update']);

    Route::post('responsibles/assign-responsible/prepare', [AssignResponsibleController::class, 'prepareAssignTags']);

    Route::post('responsibles/assign-responsible/send-blank-document', [AssignResponsibleController::class, 'sendBlankDocument']);

    Route::post('responsibles/assign-responsible/sign-document-confirm-responsible', [AssignResponsibleController::class, 'signDocumentAndConfirmResponsible']);

    Route::get('puntos/{punto}/responsibles', [ResponsibleController::class, 'showAllByPunto']);

    Route::get('estados', [DatosActivosController::class, 'estados']);

    Route::get('grupo/{ciclo}', [DatosActivosController::class, 'grupo']);

    Route::get('familia/{codigo_grupo}/{ciclo}', [DatosActivosController::class, 'familia']);

    Route::get('bienes_marcas/{id_familia}/{ciclo}', [DatosActivosController::class, 'bienes_Marcas']);

    Route::get('bienes_grupo_familia/{idCiclo}', [DatosActivosController::class, 'bienesGrupoFamilia']);

    Route::get('buscar_grupo_familia/{id_familia}', [DatosActivosController::class, 'buscarGrupoFamilia']);

    Route::get('colores', [DatosActivosController::class, 'indiceColores']);

    Route::get('estados-operacionales', [DatosActivosController::class, 'estadosOperacional']);

    Route::get('tipos-trabajo', [DatosActivosController::class, 'tipoTrabajo']);

    Route::get('cargas-trabajo', [DatosActivosController::class, 'cargaTrabajo']);

    Route::get('condiciones-ambientales', [DatosActivosController::class, 'condicionAmbiental']);

    Route::get('estados-conservacion', [DatosActivosController::class, 'estadoConservacion']);

    Route::get('materiales', [DatosActivosController::class, 'material']);

    Route::get('formas', [DatosActivosController::class, 'forma']);

    Route::post('create-bienes', [DatosActivosController::class, 'createBienes']);

    Route::post('create-marcas', [DatosActivosController::class, 'createMarcas']);

    Route::post('update-inventario', [InventariosController::class, 'updateinventario']);

    Route::post('create-inventario', [InventariosController::class, 'createinventario']);

    Route::get('configuracion/{id_grupo}', [InventariosController::class, 'configuracion']);

    Route::put('inventario/image/{etiqueta}', [InventariosController::class, 'ImageByEtiqueta']);

    Route::post('inventario/{ciclo}/procesar-varios', [InventariosController::class, 'storeInventoryMultiple']);

    Route::get('inventarioData/{in_inventario}/{id_ciclo}', [InventariosController::class, 'showData']);

    Route::get('inventario/ciclos/{ciclo}/inventario-todo', [InventariosController::class, 'getFromServerToLocalDevice']);

    Route::get('localizacion/{etiqueta}', [CrudActivoController::class, 'localizacion']);

    Route::get('marcas-disponibles/{etiqueta}', [CrudActivoController::class, 'marcasDisponibles']);

    Route::get('ciclos', [CiclosController::class, 'index']);


    Route::get('ciclos/{ciclo}', [CiclosController::class, 'show']);

    Route::get('ciclos/{ciclo}/download', [CiclosController::class, 'download']);

    Route::get('ciclos-by-user', [CiclosController::class, 'indexByUser']);

    Route::get('ciclos/{ciclo}/puntos', [CiclosUbicacionesController::class, 'show']);

    Route::get('ciclos/{ciclo}/puntos-and-zones-with-cats', [CiclosUbicacionesController::class, 'showByCycleCats']);

    Route::get('puntos', [UbicacionesActivosController::class, 'index']);

    //Route::get('zones/{zona}/emplazamientos', [ZonaEmplazamientosController::class, 'show']);

    Route::get('zones/{zona}/emplazamientos', [ZonaEmplazamientosController::class, 'show'])->middleware('roles.permissions:emplazamiento,edit');

    Route::get('ciclos/{ciclo}/zones/{zona}/emplazamientos', [ZonaEmplazamientosController::class, 'showByCycleCats']);

    Route::get('ciclos/{ciclo}/zones/{zona}/Subemplazamientos', [ZonaEmplazamientosController::class, 'CycleCatsNivel3']);

    Route::get('ciclos/{ciclo}/emplazamientos', [ZonaEmplazamientosController::class, 'showAllEmplaByCycleCats']);

    Route::get('ciclos/{ciclo}/zones/{zona}', [ZonaController::class, 'showByCycleCats']);

    Route::get('zones/{zona}', [ZonaController::class, 'show'])->name('zone.show');

    Route::put('zones/{zona}/update-direccion', [ZonaController::class, 'update']);

    Route::get('zones/{zona}/{id_ciclo}', [ZonaController::class, 'show_Direccion']);

    Route::post('zones', [ZonaController::class, 'store']);

    Route::get('ciclos/{ciclo}/zones/{zona}/activos/etiquetas', [ZonasActivosController::class, 'showOnlyLabelsByCycleCats']);

    Route::get('emplazamientos/{emplazamiento}/activos', [EmplazamientosActivosController::class, 'show']);

    Route::get('emplazamientos/{emplazamiento}/activos/etiquetas', [EmplazamientosActivosController::class, 'showOnlyLabels']);

    Route::get('puntos/{punto}/emplazamientos/{emplazamiento_code}/exists', [EmplazamientoController::class, 'existsEmplazamiento']);

    Route::get('ciclos/{ciclo}/emplazamientos/{emplazamiento}/activos/etiquetas', [EmplazamientosActivosController::class, 'showOnlyLabelsByCycleCats']);

    Route::get('ciclos/{ciclo}/puntos/{punto}/activos/etiquetas', [UbicacionesActivosController::class, 'showOnlyLabelsByCycleCats']);

    Route::get('ciclos/{ciclo}/puntos/{punto}', [CiclosUbicacionesController::class, 'showAll']);

    Route::get('emplazamientos/{emplazamiento}/{ciclo}', [EmplazamientoController::class, 'show']);

    Route::put('emplazamientos/{id}', [EmplazamientoController::class, 'update']);

    Route::post('emplazamientos/create', [EmplazamientoController::class, 'create']);

    Route::post('emplazamientos', [EmplazamientoController::class, 'store']);

    Route::post('Subemplazamientos', [EmplazamientoController::class, 'createSubEmplazamientos']);

    Route::post('Subemplazamientos/nuevo', [EmplazamientoController::class, 'createSubEmplazamientosNivel3']);

    Route::get('emplazamientosN3/{codigoUbicacionN3}', [EmplazamientoController::class, 'showN3']);

    Route::get('ciclos/{ciclo}/emplazamientos/{emplazamiento}', [CiclosEmplazamientosController::class, 'show']);

    Route::post('auditorias/procesar-conteo', [InventarioConteoController::class, 'processConteo']);

    Route::post('auditorias/procesar-conteo-por-emplazamiento', [InventarioConteoController::class, 'processConteoByEmplazamientoMultipleUsers']);

    Route::post('auditorias/procesar-conteo-por-zona', [InventarioConteoController::class, 'processConteoByZonaMultipleUsers']);

    Route::post('auditorias/procesar-conteo-por-punto', [InventarioConteoController::class, 'processConteoByAddressMultipleUsers']);

    Route::get('auditorias/ciclos/{ciclos}/conteo-por-emplazamiento/{emplazamiento}', [InventarioConteoController::class, 'showConteoByEmplazamiento']);

    Route::delete('auditorias/ciclos/{ciclos}/conteo-por-emplazamiento/{emplazamiento}/delete-sobrantes', [InventarioConteoController::class, 'deleteSobrantesConteoByEmplazamiento']);

    Route::get('auditorias/ciclos/{ciclos}/conteo-por-zona/{zona}', [InventarioConteoController::class, 'showConteoByZone']);

    Route::get('auditorias/ciclos/{ciclos}/conteo-por-punto/{punto}', [InventarioConteoController::class, 'showConteoByAddress']);

    Route::get('auditorias/ciclos/{ciclos}/conteo-todo', [InventarioConteoController::class, 'showAllConteo']);

    Route::delete('auditorias/ciclos/{ciclos}/conteo-por-punto/{punto}/delete-sobrantes', [InventarioConteoController::class, 'deleteSobrantesConteoByAddress']);

    Route::post('auditorias/reset-conteo-emplazamiento', [InventarioConteoController::class, 'resetConteoByEmplazamiento']);

    Route::post('auditorias/reset-conteo-zona', [InventarioConteoController::class, 'resetConteoByZona']);

    Route::post('auditorias/reset-conteo-punto', [InventarioConteoController::class, 'resetConteoByAddress']);

    //Maps
    Route::apiResource('maps/areas', MapPolygonController::class)->only(['index', 'store', 'update', 'destroy']);

    Route::apiResource('maps/markers', MapMarkerController::class)->only(['index', 'store', 'update', 'destroy']);

    Route::get('maps/areas/{id}/areas', [MapPolygonController::class, 'showMarkers'])->name('maps.areas.show');
});


Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {

    Route::get('my-user-info', [UserController::class, 'show']);

    Route::post('users/register-signature', [UserController::class, 'registerSignature']);

    Route::post('logout', [
        App\Http\Controllers\Api\LoginController::class,
        'logout'
    ]);
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
