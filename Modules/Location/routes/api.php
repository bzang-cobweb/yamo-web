<?php

// Routes d'API du module Location (préfixe `api`, noms `api.`) : chaque route qui agit
// sur un modèle porte la garde `can:` de sa policy (bza-laravel:create-policy).

use Illuminate\Support\Facades\Route;
use Modules\Location\Http\Controllers\Api\AddressController;
use Modules\Location\Http\Controllers\Api\AddressSuggestionController;
use Modules\Location\Models\Address;
use Modules\Location\Models\UserAddress;

Route::middleware('auth:sanctum')->group(function () {
    // Adresses du compte (décisions 2026-11-18-adresses-liste-generique et
    // 2026-11-18-adresse-du-compte-modele). Celle d'un autre compte répond 404
    // (`UserAddressPolicy`).
    Route::prefix('v1/auth/me/addresses')->name('v1.auth.me.addresses.')->group(function () {
        Route::post('/', [AddressController::class, 'store'])->can('create', UserAddress::class)->name('store');
        Route::patch('{userAddress}', [AddressController::class, 'update'])->can('update', 'userAddress')->name('update');
        Route::delete('{userAddress}', [AddressController::class, 'destroy'])->can('delete', 'userAddress')->name('destroy');
    });

    // Suggestions pendant la saisie de la rue : 30 recherches par minute au plus.
    Route::get('v1/addresses/suggestions', [AddressSuggestionController::class, 'index'])
        ->can('viewAny', Address::class)
        ->middleware('throttle:30,1')
        ->name('v1.addresses.suggestions');
});
