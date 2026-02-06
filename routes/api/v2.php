<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V2\EmplazamientoNivel1Controller;
use App\Http\Controllers\Api\V2\EmplazamientoNivel2Controller;
use App\Http\Controllers\Api\V2\EmplazamientoNivel3Controller;
use App\Http\Controllers\Api\V1\CiclosEmplazamientosController;
use App\Http\Controllers\Api\V1\CiclosUbicacionesController;
use App\Http\Controllers\Api\V1\ResponsibleController;

Route::middleware(['auth:sanctum', 'switch.database'])->prefix('v2')->group(function () {

    Route::get('ciclos/{ciclo}/emplazamientos-n1/{emplazamiento}', [EmplazamientoNivel1Controller::class, 'show']);

    Route::get('ciclos/{ciclo}/emplazamientos-n2/{emplazamiento}', [EmplazamientoNivel2Controller::class, 'show']);

    Route::get('ciclos/{ciclo}/emplazamientos-n3/{emplazamiento}', [EmplazamientoNivel3Controller::class, 'show']);

    Route::get('ciclos/{ciclo}/emplazamientos-n1/{emplazamiento}/assets', [CiclosEmplazamientosController::class, 'showAssetsN1']);

    Route::get('ciclos/{ciclo}/emplazamientos-n2/{emplazamiento}/assets', [CiclosEmplazamientosController::class, 'showAssetsN2']);

    Route::get('ciclos/{ciclo}/emplazamientos-n3/{emplazamiento}/assets', [CiclosEmplazamientosController::class, 'showAssetsN3']);

    Route::get('ciclos/{ciclo}/emplazamientos-n1/{emplazamiento}/group-families', [CiclosEmplazamientosController::class, 'showGroupFamiliesN1']);

    Route::get('ciclos/{ciclo}/emplazamientos-n2/{emplazamiento}/group-families', [CiclosEmplazamientosController::class, 'showGroupFamiliesN2']);

    Route::get('ciclos/{ciclo}/emplazamientos-n3/{emplazamiento}/group-families', [CiclosEmplazamientosController::class, 'showGroupFamiliesN3']);

    Route::get('ciclos/{ciclo}/puntos/{punto}/assets', [CiclosUbicacionesController::class, 'showAssets']);

    Route::get('ciclos/{ciclo}/assets', [CiclosUbicacionesController::class, 'showAssetsbyCycle']);

    Route::get('ciclos/{ciclo}/puntos/{punto}/group-families', [CiclosUbicacionesController::class, 'showGroupFamilies']);

    Route::get('ciclos/{ciclo}/group-families-OT', [CiclosUbicacionesController::class, 'showGroupFamiliesByCycle']);

    Route::post('responsibles/{responsable_id}/register-signature', [ResponsibleController::class, 'registerSignature']);
});
