<?php

use App\Http\Controllers\Api\LocaleController;
use App\Http\Controllers\Api\MessageController;
use Illuminate\Support\Facades\Route;

// Routes d'API de l'application (préfixe `api`). Les routes des modules sont dans
// Modules/<Module>/routes/api.php — l'authentification est dans le module Auth
// (`/api/v1/auth/*`), les formulaires dans le module Form (`/api/v1/forms/*`).
// Chaque route qui agit sur un modèle porte la garde `can:` de sa policy
// (bza-laravel:create-policy).

Route::get('v1/locales', [LocaleController::class, 'index'])->name('api.v1.locales.index');
Route::get('v1/messages', [MessageController::class, 'index'])->name('api.v1.messages.index');
