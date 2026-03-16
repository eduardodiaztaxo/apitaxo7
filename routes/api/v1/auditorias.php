<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\Auditoria\InventarioConteoController;
use App\Http\Controllers\Api\V2\Auditoria\AuditoriaConteoController;
use App\Http\Controllers\Api\V2\Auditoria\CiclosAuditoriaUbicacionesController;

Route::middleware(['auth:sanctum', 'switch.database'])->prefix('v1')->group(function () {

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
});

Route::middleware(['auth:sanctum', 'switch.database'])->prefix('v2/auditoria')->group(function () {

    Route::get('ciclos/{ciclo}/puntos-with-assets-contain-group-family', [CiclosAuditoriaUbicacionesController::class, 'showByCycleAndGrupFamily']);

    Route::get('ciclos/{ciclo}/detalle-punto/{punto}', [CiclosAuditoriaUbicacionesController::class, 'showOne']);

    Route::get('ciclos/{ciclo}/puntos/{punto}/assets-contain-group-family', [CiclosAuditoriaUbicacionesController::class, 'showAssetsByUbicacion']);

    Route::get('ciclos/{ciclo}/puntos/{punto}/group-families', [CiclosAuditoriaUbicacionesController::class, 'showGroupFamilies']);

    Route::post('conteo/ciclo/{ciclo}/punto/{punto}/codigo/{codigo}/subnivel/{subnivel}/resumen', [AuditoriaConteoController::class, 'showResumen']);

    Route::post('conteo/ciclo/{ciclo}/punto/{punto}/codigo/{codigo}/subnivel/{subnivel}/resultados', [AuditoriaConteoController::class, 'showAssetsResults']);

    Route::get('conteo/ciclo/{ciclo}/punto/{punto}/codigo/{codigo}/subnivel/{subnivel}/solo-etiquetas-a-auditar', [AuditoriaConteoController::class, 'showOnlyTagsToAuditByPlace']);
});
