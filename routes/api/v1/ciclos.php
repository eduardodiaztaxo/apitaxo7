<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\CiclosController;
use App\Http\Controllers\Api\V1\CiclosUbicacionesController;
use App\Http\Controllers\Api\V1\CiclosEmplazamientosController;
use App\Http\Controllers\Api\V2\Auditoria\CiclosAuditoriaUbicacionesController;

Route::middleware(['auth:sanctum', 'switch.database'])->prefix('v1')->group(function () {

    Route::get('ciclos', [CiclosController::class, 'index']);

    Route::get('ciclos/{ciclo}', [CiclosController::class, 'show']);

    Route::get('ciclos/{ciclo}/download', [CiclosController::class, 'download']);

    Route::get('ciclos-by-user', [CiclosController::class, 'indexByUser']);

    Route::get('ciclos/{ciclo}/puntos', [CiclosUbicacionesController::class, 'show']);

    Route::get('ciclos/{ciclo}/puntos-little', [CiclosUbicacionesController::class, 'showLittle']);

    Route::get('ciclos/{ciclo}/puntos-and-zones-with-cats', [CiclosUbicacionesController::class, 'showByCycleCats']);

    Route::post('create-direcciones', [CiclosUbicacionesController::class, 'store']);

    Route::get('ciclos/{ciclo}/zones/{zona}/emplazamientos/{agenda_id}', [CiclosUbicacionesController::class, 'showByCycleCats']);

    Route::get('ciclos/{ciclo}/puntos/{punto}', [CiclosUbicacionesController::class, 'showAll']);

    Route::get('ciclos/{ciclo}/detail', [CiclosUbicacionesController::class, 'showAllCycle']);

    Route::get('ciclos/{ciclo}/emplazamientos/{emplazamiento}', [CiclosEmplazamientosController::class, 'show']);

    Route::get('ciclos/{ciclo}/emplazamientos/{emplazamiento}/group-families', [CiclosEmplazamientosController::class, 'showGroupFamilies']);

    Route::get('ciclos/{ciclo}/emplazamientos/{emplazamiento}/assets', [CiclosEmplazamientosController::class, 'showAssetsN2']);
});


Route::middleware(['auth:sanctum', 'switch.database'])->prefix('v2/auditoria')->group(function () {

    Route::get('ciclos/{ciclo}/puntos-with-assets-contain-group-family', [CiclosAuditoriaUbicacionesController::class, 'showByCycleAndGrupFamily']);

    Route::get('ciclos/{ciclo}/detalle-punto/{punto}', [CiclosAuditoriaUbicacionesController::class, 'showOne']);
});
