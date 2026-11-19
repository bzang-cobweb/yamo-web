<?php

// Routes d'API du module Location (préfixe `api`, noms `api.`) : chaque route qui agit
// sur un modèle porte la garde `can:` de sa policy (bza-laravel:create-policy).
// Les adresses du compte sont servies par le module Auth (décision
// 2026-11-18-useraddress-dans-auth).

use Illuminate\Support\Facades\Route;
use Modules\Location\Http\Controllers\Api\AddressSuggestionController;
use Modules\Location\Http\Controllers\Api\CountryController;

// Pays proposés à l'entrée de l'app, avant connexion (décision 2026-11-18-espace-pays).
Route::get('v1/countries', [CountryController::class, 'index'])->name('v1.countries.index');

// Suggestions d'adresses pendant la saisie d'une rue, pour un compte connecté
// (décision 2026-11-19-contrat-adresse-offre-recherche).
Route::middleware(['auth:sanctum', 'throttle:60,1'])
    ->get('v1/addresses/suggestions', [AddressSuggestionController::class, 'index'])
    ->name('v1.addresses.suggestions');
