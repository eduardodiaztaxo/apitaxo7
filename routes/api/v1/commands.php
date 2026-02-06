<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\Tasks\CommandController;

Route::middleware(['auth:sanctum', 'switch.database'])->prefix('v1')->group(function () {
    Route::post('commands/relate-markers-to-areas', [CommandController::class, 'runRelateMarkersToAreasCommand']);
    Route::post('commands/relate-inventory-markers-to-areas', [CommandController::class, 'runRelateInventoryMarkersToAreasCommand']);
});
