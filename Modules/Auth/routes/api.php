<?php

use Illuminate\Support\Facades\Route;
use Modules\Auth\Http\Controllers\Api\LoginController;
use Modules\Auth\Http\Controllers\Api\LogoutController;
use Modules\Auth\Http\Controllers\Api\ProfileController;
use Modules\Auth\Models\User;

// Routes d'API du module Auth (préfixe `api`, noms `api.`) : chaque route qui agit
// sur un modèle porte la garde `can:` de sa policy (bza-laravel:create-policy).

Route::prefix('v1/auth')->name('v1.auth.')->group(function () {
    Route::post('login', [LoginController::class, 'login'])
        ->middleware('throttle:auth-login')
        ->name('login');

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('logout', [LogoutController::class, 'logout'])->name('logout');
        Route::get('me', [ProfileController::class, 'show'])->can('me', User::class)->name('me');
        Route::patch('me', [ProfileController::class, 'update'])->can('updateMe', User::class)->name('me.update');
        Route::post('me/avatar', [ProfileController::class, 'updateAvatar'])->can('updateMe', User::class)->name('me.avatar.update');
        Route::delete('me/avatar', [ProfileController::class, 'destroyAvatar'])->can('updateMe', User::class)->name('me.avatar.destroy');
    });
});
