<?php

use Illuminate\Support\Facades\Route;
use Modules\Auth\Http\Controllers\Api\AuthController;
use Modules\Auth\Models\User;

// Routes d'API du module Auth (préfixe `api`, noms `api.`) : chaque route qui agit
// sur un modèle porte la garde `can:` de sa policy (bza-laravel:create-policy).

Route::prefix('v1/auth')->name('v1.auth.')->group(function () {
    Route::post('login', [AuthController::class, 'login'])
        ->middleware('throttle:auth-login')
        ->name('login');

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('logout', [AuthController::class, 'logout'])->name('logout');
        Route::get('me', [AuthController::class, 'me'])->can('me', User::class)->name('me');
        Route::patch('me', [AuthController::class, 'updateMe'])->can('updateMe', User::class)->name('me.update');
    });
});
