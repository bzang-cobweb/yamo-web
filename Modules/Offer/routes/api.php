<?php

// Routes d'API du module Offer (préfixe `api`, noms `api.`) : chaque route qui agit
// sur un modèle porte la garde `can:` de sa policy (bza-laravel:create-policy).
// Sans domaine choisi, elles répondent 404 (décision 2026-11-18-offres-et-domaines).

use Illuminate\Support\Facades\Route;
use Modules\Offer\Http\Controllers\Api\OfferController;
use Modules\Offer\Http\Middleware\RequireOfferDomain;
use Modules\Offer\Models\Offer;

// Fiche d'une offre, ouverte sans connexion (décision 2026-11-18-fiche-offre) :
// l'autorisation (policy `show`, compte facultatif) est faite dans le contrôleur.
Route::middleware(RequireOfferDomain::class)->get('v1/offers/{offer}', [OfferController::class, 'show'])->name('v1.offers.show');

Route::middleware(['auth:sanctum', RequireOfferDomain::class])->prefix('v1/offers')->name('v1.offers.')->group(function () {
    Route::post('/', [OfferController::class, 'store'])->can('create', Offer::class)->name('store');
    Route::patch('{offer}', [OfferController::class, 'update'])->can('update', 'offer')->name('update');
    Route::delete('{offer}', [OfferController::class, 'destroy'])->can('delete', 'offer')->name('destroy');
});
