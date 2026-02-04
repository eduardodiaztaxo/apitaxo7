<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\ZonaController;
use App\Http\Controllers\Api\V1\ZonaEmplazamientosController;
use App\Http\Controllers\Api\V1\ZonasActivosController;

Route::middleware(['auth:sanctum', 'switch.database'])->prefix('v1')->group(function () {

    Route::get('zones/{zona}/emplazamientos', [ZonaEmplazamientosController::class, 'show'])->middleware('roles.permissions:emplazamiento,edit');

    Route::get('regiones', [ZonaEmplazamientosController::class, 'regiones']);

    Route::get('comunas/{idRegion}', [ZonaEmplazamientosController::class, 'comunas']);

    Route::get('ciclos/{ciclo}/zones/{zona}/emplazamientos/{agenda_id}', [ZonaEmplazamientosController::class, 'showByCycleCats']);

    Route::get('ciclos/{ciclo}/zones/{zona}/Subemplazamientos/{agenda_id}', [ZonaEmplazamientosController::class, 'CycleCatsNivel3']);

    Route::get('ciclos/{ciclo}/zones/{zona}/activos/etiquetas', [ZonasActivosController::class, 'showOnlyLabelsByCycleCats']);

    Route::get('ciclos/{ciclo}/zones/{zona}', [ZonaController::class, 'showByCycleCats']);

    Route::get('zones/{zona}', [ZonaController::class, 'show'])->name('zone.show');

    Route::put('zones/{zona}/update-direccion', [ZonaController::class, 'update']);

    Route::get('zones/{zona}/{id_ciclo}', [ZonaController::class, 'show_Direccion']);

    Route::post('zones', [ZonaController::class, 'store']);

});
