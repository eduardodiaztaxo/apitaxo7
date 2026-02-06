<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\CiclosController;
use App\Http\Controllers\Api\V1\CiclosUbicacionesController;
use App\Http\Controllers\Api\V1\CiclosEmplazamientosController;

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
