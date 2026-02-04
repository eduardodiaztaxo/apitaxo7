<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\TestConnectionController;
use App\Http\Controllers\Api\V1\PostController as PostV1;
use App\Http\Controllers\Api\V1\BajaDocumentoController as BajaV1;
use App\Http\Controllers\Api\V1\Comunes\DatosActivosController;
use App\Http\Controllers\Api\V1\RespLocalizacionController as RespLocV1;

// Importar rutas por entidad
require __DIR__ . '/activos.php';
require __DIR__ . '/inventarios.php';
require __DIR__ . '/ciclos.php';
require __DIR__ . '/responsibles.php';
require __DIR__ . '/zones.php';
require __DIR__ . '/emplazamientos.php';
require __DIR__ . '/auditorias.php';
require __DIR__ . '/maps.php';

Route::middleware(['auth:sanctum', 'switch.database'])->prefix('v1')->group(function () {

    Route::get('pin', [App\Http\Controllers\Api\LoginController::class, 'pin']);

    Route::get('test/connection', [TestConnectionController::class, 'pin']);

    Route::apiResource('posts', PostV1::class)->only(['index', 'show', 'destroy']);

    Route::apiResource('bajas', BajaV1::class)->only(['index', 'show', 'destroy']);

    Route::apiResource('localizaciones/actualizar', RespLocV1::class)->only(['store']);

    Route::post('localizaciones/actualizar-multiples', [RespLocV1::class, 'storeMultiple']);

    Route::post('generate-encrypt-password', [
        App\Http\Controllers\Api\LoginController::class,
        'makePassword'
    ]);

    Route::get('marcas', [DatosActivosController::class, 'marcas']);

    Route::get('responsables', [DatosActivosController::class, 'responsables']);

    Route::get('estados', [DatosActivosController::class, 'estados']);

    Route::get('grupo/{ciclo}', [DatosActivosController::class, 'grupo']);

    Route::get('familia/{codigo_grupo}/{ciclo}', [DatosActivosController::class, 'familia']);

    Route::get('bienes_marcas/{id_familia}', [DatosActivosController::class, 'bienes_Marcas']);

    Route::get('bienes_grupo_familia/{idCiclo}', [DatosActivosController::class, 'bienesGrupoFamilia']);

    Route::get('bienes-grupo-familia/{cycle_id}', [DatosActivosController::class, 'showAllByBienesGrupoFamilia']);

    Route::get('bienes-grupo-familia/{cycle_id}/count-all', [DatosActivosController::class, 'countAllByBienesGrupoFamilia']);

    Route::get('buscar_grupo_familia/{id_familia}', [DatosActivosController::class, 'buscarGrupoFamilia']);

    Route::get('colores', [DatosActivosController::class, 'indiceColores']);

    Route::get('estados-operacionales', [DatosActivosController::class, 'estadosOperacional']);

    Route::get('tipos-trabajo', [DatosActivosController::class, 'tipoTrabajo']);

    Route::get('cargas-trabajo', [DatosActivosController::class, 'cargaTrabajo']);

    Route::get('condiciones-ambientales', [DatosActivosController::class, 'condicionAmbiental']);

    Route::get('estados-conservacion', [DatosActivosController::class, 'estadoConservacion']);

    Route::get('materiales', [DatosActivosController::class, 'material']);

    Route::get('formas', [DatosActivosController::class, 'forma']);

    Route::post('create-bienes', [DatosActivosController::class, 'createBienes']);

    Route::post('create-marcas', [DatosActivosController::class, 'createMarcas']);

});
