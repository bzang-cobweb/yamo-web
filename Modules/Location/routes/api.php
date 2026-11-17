<?php

// Routes d'API du module Location (préfixe `api`, noms `api.`) : chaque route qui agit
// sur un modèle porte la garde `can:` de sa policy (bza-laravel:create-policy).

use Illuminate\Support\Facades\Route;
use Modules\Location\Http\Controllers\Api\AddressController;
use Modules\Location\Models\Address;

// Adresses du compte (décision 2026-11-18-adresses-liste-generique). L'adresse
// d'un autre compte répond 404 (`AddressPolicy`).
Route::middleware('auth:sanctum')->prefix('v1/auth/me/addresses')->name('v1.auth.me.addresses.')->group(function () {
    Route::post('/', [AddressController::class, 'store'])->can('create', Address::class)->name('store');
    Route::patch('{address}', [AddressController::class, 'update'])->can('update', 'address')->name('update');
    Route::delete('{address}', [AddressController::class, 'destroy'])->can('detach', 'address')->name('destroy');
});
