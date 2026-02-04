<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\InventariosController;

Route::middleware(['auth:sanctum', 'switch.database'])->prefix('v1')->group(function () {

    Route::post('update-inventario', [InventariosController::class, 'updateinventario']);

    Route::post('create-inventario', [InventariosController::class, 'createinventario']);

    Route::get('configuracion/{id_grupo}/cycle/{cycleid}', [InventariosController::class, 'configuracion']);

    Route::get('activos/images/etiqueta/{etiqueta}/cycle/{cycleid}/idActivo/{idActivo}', [InventariosController::class, 'getImagesByEtiqueta']);

    Route::post('activos/delete-image/etiqueta/{etiqueta}/id_img/{id_img}/idLista/{idLista}/idActivo/{idActivo?}/cycle/{cycleid}', [InventariosController::class, 'deleteImageByEtiqueta']);

    Route::post('inventario/add-image/{etiqueta}', [InventariosController::class, 'addImageByEtiqueta']);

    Route::get('rango-permitido/{idAgenda}', [InventariosController::class, 'rangoPermitido']);

    Route::put('inventario/{ciclo}/ajustar-coordenadas/etiqueta/{etiqueta}', [InventariosController::class, 'updateAdjustCoordinatesInventory']);

    Route::put('inventario/{ciclo}/ajustar-coordenadas-debug-data/etiqueta/{etiqueta}', [InventariosController::class, 'updateAdjustCoordinatesInventoryDebugData']);

    Route::get('nombre-inputs', [InventariosController::class, 'nombreInputs']);

    Route::put('inventario/image/{etiqueta}', [InventariosController::class, 'ImageByEtiqueta']);

    Route::post('inventario/{ciclo}/procesar-varios', [InventariosController::class, 'storeInventoryMultiple']);

    Route::get('inventarioData/{in_inventario}/{id_ciclo}', [InventariosController::class, 'showData']);

    Route::get('inventario/ciclos/{ciclo}/inventario-todo', [InventariosController::class, 'getFromServerToLocalDevice']);

    Route::get('inventario/cycle/{cycle}/etiqueta/{etiqueta}', [InventariosController::class, 'showByEtiqueta']);

});
