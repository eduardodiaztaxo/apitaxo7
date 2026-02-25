<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\User\UserInteractionController; 

Route::post('user_interaction', [UserInteractionController::class, 'saveInteraction']);