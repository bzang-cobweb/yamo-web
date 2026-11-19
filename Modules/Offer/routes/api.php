<?php

// Routes d'API du module Offer (préfixe `api`, noms `api.`) : chaque route qui agit
// sur un modèle porte la garde `can:` de sa policy (bza-laravel:create-policy).
// Sans domaine choisi, elles répondent 404 (décision 2026-11-18-offres-et-domaines).

use Illuminate\Support\Facades\Route;
use Modules\Offer\Http\Controllers\Api\BookingController;
use Modules\Offer\Http\Controllers\Api\OfferController;
use Modules\Offer\Http\Controllers\Api\OfferFavoriteController;
use Modules\Offer\Http\Controllers\Api\OfferReviewController;
use Modules\Offer\Http\Middleware\RequireOfferDomain;
use Modules\Offer\Models\Offer;

// Fiche d'une offre, ouverte sans connexion (décision 2026-11-18-fiche-offre) :
// l'autorisation (policy `show`, compte facultatif) est faite dans le contrôleur.
Route::middleware(RequireOfferDomain::class)->get('v1/offers/{offer}', [OfferController::class, 'show'])->name('v1.offers.show');
// Tous ses avis, par pages, ouverts sans connexion comme la fiche (décision 2026-11-19-nuit-tous-les-avis).
Route::middleware([RequireOfferDomain::class, 'throttle:60,1'])->get('v1/offers/{offer}/reviews', [OfferReviewController::class, 'index'])->name('v1.offers.reviews.index');

Route::middleware(['auth:sanctum', RequireOfferDomain::class])->prefix('v1/offers')->name('v1.offers.')->group(function () {
    Route::post('/', [OfferController::class, 'store'])->can('create', Offer::class)->name('store');
    Route::patch('{offer}', [OfferController::class, 'update'])->can('update', 'offer')->name('update');
    Route::delete('{offer}', [OfferController::class, 'destroy'])->can('delete', 'offer')->name('destroy');

    // Son avis sur une offre (décision 2026-11-19-contrat-avis-offres).
    Route::put('{offer}/review', [OfferReviewController::class, 'save'])->can('review', 'offer')->name('review.save');
    Route::delete('{offer}/review', [OfferReviewController::class, 'destroy'])->can('review', 'offer')->name('review.destroy');

    // Demander une réservation ou une visite (décision 2026-11-19-contrat-reservations).
    Route::post('{offer}/bookings', [BookingController::class, 'store'])->can('book', 'offer')->middleware('throttle:20,1')->name('bookings.store');

    // Ses favoris (décision 2026-11-19-contrat-vedette-favoris).
    Route::put('{offer}/favorite', [OfferFavoriteController::class, 'save'])->can('favorite', 'offer')->name('favorite.save');
    Route::delete('{offer}/favorite', [OfferFavoriteController::class, 'destroy'])->can('favorite', 'offer')->name('favorite.destroy');
});

// Répondre à une demande (le propriétaire), l'annuler (le membre) : décision 2026-11-19-contrat-reservations.
Route::middleware(['auth:sanctum', RequireOfferDomain::class])->prefix('v1/bookings')->name('v1.bookings.')->group(function () {
    Route::post('{booking}/answer', [BookingController::class, 'answer'])->can('answer', 'booking')->name('answer');
    // Accepter d'un geste, depuis la carte de la demande (décision 2026-11-19-contrat-reservations-logements-profil).
    Route::post('{booking}/accept', [BookingController::class, 'accept'])->can('answer', 'booking')->name('accept');
    Route::post('{booking}/cancel', [BookingController::class, 'cancel'])->can('cancel', 'booking')->name('cancel');
});
