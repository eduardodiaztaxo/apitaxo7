<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\ResponsibleController;
use App\Http\Controllers\Api\V1\Responsible\AssignResponsibleController;

Route::middleware(['auth:sanctum', 'switch.database'])->prefix('v1')->group(function () {

    Route::get('responsibles', [ResponsibleController::class, 'showAll']);

    Route::get('responsibles/{id}', [ResponsibleController::class, 'show']);

    Route::post('responsibles', [ResponsibleController::class, 'store']);

    Route::put('responsibles/{id}', [ResponsibleController::class, 'update']);

    Route::post('responsibles/assign-responsible/prepare', [AssignResponsibleController::class, 'prepareAssignTags']);

    Route::post('responsibles/assign-responsible/send-blank-document', [AssignResponsibleController::class, 'sendBlankDocument']);

    Route::post('responsibles/assign-responsible/sign-document-confirm-responsible', [AssignResponsibleController::class, 'signDocumentAndConfirmResponsible']);

    Route::get('puntos/{punto}/responsibles', [ResponsibleController::class, 'showAllByPunto']);

});
