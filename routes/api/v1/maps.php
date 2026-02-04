<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\Maps\MapCategoryController;
use App\Http\Controllers\Api\V1\Maps\MapLandMarkerController;
use App\Http\Controllers\Api\V1\Maps\MapMarkerController;
use App\Http\Controllers\Api\V1\Maps\MapPolygonController;
use App\Http\Controllers\Api\V1\Maps\MapReportMarkerController;

Route::middleware(['auth:sanctum', 'switch.database'])->prefix('v1')->group(function () {

    Route::apiResource('maps/areas', MapPolygonController::class)->only(['index', 'store', 'update', 'destroy']);

    Route::apiResource('maps/markers', MapMarkerController::class)->only(['index', 'store', 'update', 'destroy']);

    Route::get('maps/area-by-address/{address_id}', [MapPolygonController::class, 'showPolygonByAddress']);

    Route::get('maps/areas/base', [MapPolygonController::class, 'indexBase']);

    Route::get('maps/areas/descendants/{parent_id}', [MapPolygonController::class, 'getDescendants']);

    Route::put('maps/inventory-markers/{id}', [MapPolygonController::class, 'updateInventoryMarker']);

    Route::get('maps/areas/{id}/markers', [MapPolygonController::class, 'showMarkers'])->name('maps.areas.showMarkers');

    Route::get('maps/areas/{id}/inventory-markers', [MapPolygonController::class, 'showInventoryMarkers'])->name('maps.areas.showInvMarkers');

    Route::get('maps/markers/categories', [MapCategoryController::class, 'index']);

    Route::get('maps/land-markers', [MapLandMarkerController::class, 'index']);

    Route::get('maps/landmarkers-by-area/{area_id}', [MapLandMarkerController::class, 'indexByArea']);

    Route::get('maps/overlapping-inventory-markers', [MapReportMarkerController::class, 'indexOverlappingInventoryMarkers']);

    Route::get('maps/users-inventory-markers', [MapReportMarkerController::class, 'indexUsersInventoryMarkers']);

});
