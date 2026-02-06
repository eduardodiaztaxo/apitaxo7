<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\CrudActivoController;
use App\Http\Controllers\Api\V1\RespActivoController as RespActivoV1;

Route::middleware(['auth:sanctum', 'switch.database'])->prefix('v1')->group(function () {

    Route::apiResource('activos/actualizar', RespActivoV1::class)->only(['store']);

    Route::post('activos/actualizar-multiples', [RespActivoV1::class, 'storeMultiple_v2']);

    Route::post('activos/actualizar-multiples-v2', [RespActivoV1::class, 'storeMultiple_v2']);

    Route::get('activos/etiqueta/{etiqueta}/ciclo/{ciclo}', [CrudActivoController::class, 'showByEtiqueta']);

    Route::get('activos/inventario/{id}', [CrudActivoController::class, 'showInventoryByID']);

    Route::post('activos/etiquetas', [CrudActivoController::class, 'showByEtiquetas']);

    Route::post('activos/not-responsibles/etiquetas', [CrudActivoController::class, 'showByEtiquetasWithoutResponsibles']);

    Route::put('activos/upload-image/etiqueta/{etiqueta}', [CrudActivoController::class, 'uploadImageByEtiqueta']);

    Route::put('activos/update/etiqueta/{etiqueta}', [CrudActivoController::class, 'update']);

    Route::get('activos-show/{etiqueta}', [CrudActivoController::class, 'showActivos']);

    Route::get('localizacion/{etiqueta}', [CrudActivoController::class, 'localizacion']);

    Route::get('marcas-disponibles/{etiqueta}/{ciclo}', [CrudActivoController::class, 'marcasDisponibles']);

});
