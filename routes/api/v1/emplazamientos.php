<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\EmplazamientoController;
use App\Http\Controllers\Api\V1\EmplazamientosActivosController;
use App\Http\Controllers\Api\V1\UbicacionesActivosController;
use App\Http\Controllers\Api\V1\ZonaEmplazamientosController;

Route::middleware(['auth:sanctum', 'switch.database'])->prefix('v1')->group(function () {

    Route::get('puntos', [UbicacionesActivosController::class, 'index']);

    Route::get('ciclos/{ciclo}/emplazamientosN1/{agenda_id}', [ZonaEmplazamientosController::class, 'CycleCatsNivel1']);

    Route::get('ciclos/{ciclo}/emplazamientos-select-n2/{agenda_id}', [ZonaEmplazamientosController::class, 'selectEmplazamientosN2']);

    Route::get('ciclos/{ciclo}/emplazamientos-select-n3/{agenda_id}', [ZonaEmplazamientosController::class, 'selectEmplazamientosN3']);

    Route::get('mover-emplazamientos/{codigoUbicacion}/{ciclo_id}/{agenda_id}/{etiqueta}', [EmplazamientoController::class, 'moverEmplazamientos']);

    Route::get('ciclos/{cycle_id}/puntos/{address_id}/move-address/etiqueta/{etiqueta}', [UbicacionesActivosController::class, 'moveAddress']);

    Route::get('ciclos/{ciclo}/emplazamientos', [ZonaEmplazamientosController::class, 'showAllEmplaByCycleCats']);

    Route::get('emplazamientos/{emplazamiento}/activos', [EmplazamientosActivosController::class, 'show']);

    Route::get('emplazamientos/{emplazamiento}/activos/etiquetas', [EmplazamientosActivosController::class, 'showOnlyLabels']);

    Route::get('puntos/{punto}/emplazamientos/{emplazamiento_code}/exists', [EmplazamientoController::class, 'existsEmplazamiento']);

    Route::get('ciclos/{ciclo}/emplazamientos/{emplazamiento}/activos/etiquetas', [EmplazamientosActivosController::class, 'showOnlyLabelsByCycleCats']);

    Route::get('ciclos/{ciclo}/puntos/{punto}/activos/etiquetas', [UbicacionesActivosController::class, 'showOnlyLabelsByCycleCats']);

    Route::get('emplazamientos/{emplazamiento}/{ciclo}/{codigoUbicacion}', [EmplazamientoController::class, 'showOne']);

    // Specific routes first to avoid being caught by generic ones
    Route::get('todos-emplazamientos/pictures/{etiqueta}', [EmplazamientoController::class, 'getAssetPictures']);

    // allow optional level segment to support generic resource responses
    Route::get('todos-emplazamientos/{idAgenda}/{ciclo}/{nivel?}', [EmplazamientoController::class, 'showTodos']);

    Route::get('todos-emplazamientos/{idAgenda}/{ciclo}/{nivel}/{codigoUbicacion}/assets', [EmplazamientoController::class, 'showTodosAssets']);

    Route::get('group-emplazamientos/{idAgenda}/{ciclo}', [EmplazamientoController::class, 'groupEmplazamientos']);

    Route::get('group-emplazamientos-Ot/{ciclo}', [EmplazamientoController::class, 'groupEmplazamientosPorOt']);

    Route::get('group-map-direccion-diferencias/{idAgenda}/{ciclo}', [EmplazamientoController::class, 'groupMapDireccionDiferencias']);

    Route::get('group-map-Ot-diferencias/{ciclo}', [EmplazamientoController::class, 'groupMapDiferenciasOT']);

    Route::put('emplazamientos/{id}', [EmplazamientoController::class, 'update']);

    Route::post('emplazamientos/create', [EmplazamientoController::class, 'create']);

    Route::post('emplazamientos', [EmplazamientoController::class, 'store']);



    Route::post('Subemplazamientos/nuevo', [EmplazamientoController::class, 'createSubEmplazamientosNivel3']);

    Route::get('emplazamientos-todos/{lastN1Id}/{lastN2Id}/{lastN3Id}', [EmplazamientoController::class, 'showTodosEmplazamientosN1N2N3']);
});

Route::middleware(['auth:sanctum', 'switch.database'])->prefix('v2')->group(function () {
    Route::post('emplazamientos/create-any-level', [EmplazamientoController::class, 'storeAnyLevel']);
    Route::get('inventario/ciclos/{cycle_id}/puntos/{address_id}/parent-code/{codigoUbicacion}/nivel/{nivel}/sublevels', [EmplazamientoController::class, 'getSublevelsWithAssetsContainGroupFamily']);
});
