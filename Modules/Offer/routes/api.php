<?php

// Routes d'API du module Offer (préfixe `api`, noms `api.`) : chaque route qui agit
// sur un modèle porte la garde `can:` de sa policy (bza-laravel:create-policy).
// Sans domaine choisi, elles répondent 404 (décision 2026-11-18-offres-et-domaines).

use Illuminate\Support\Facades\Route;
use Modules\Offer\Http\Controllers\Api\BookingController;
use Modules\Offer\Http\Controllers\Api\HomeController;
use Modules\Offer\Http\Controllers\Api\MemberController;
use Modules\Offer\Http\Controllers\Api\OfferCalendarController;
use Modules\Offer\Http\Controllers\Api\OfferController;
use Modules\Offer\Http\Controllers\Api\OfferFavoriteController;
use Modules\Offer\Http\Controllers\Api\OfferReviewController;
use Modules\Offer\Http\Middleware\RequireOfferDomain;
use Modules\Offer\Models\Offer;

// Fiche d'une offre, ouverte sans connexion (décision 2026-11-18-fiche-offre) :
// l'autorisation (policy `show`, compte facultatif) est faite dans le contrôleur.
// L'accueil de l'app : un carrousel par ville (décision 2026-11-23-accueil-carrousels-par-ville).
Route::middleware([RequireOfferDomain::class, 'throttle:60,1'])->get('v1/home', [HomeController::class, 'index'])->name('v1.home');
Route::middleware(RequireOfferDomain::class)->get('v1/offers/{offer}', [OfferController::class, 'show'])->name('v1.offers.show');
// La fiche ouverte, par n'importe quel lien : un clic de plus (décision 2026-11-25-clic-a-l-ouverture).
Route::middleware([RequireOfferDomain::class, 'throttle:30,1'])->post('v1/offers/{offer}/click', [OfferController::class, 'click'])->whereNumber('offer')->name('v1.offers.click');
// Tous ses avis, par pages, ouverts sans connexion comme la fiche (décision 2026-11-19-nuit-tous-les-avis).
Route::middleware([RequireOfferDomain::class, 'throttle:60,1'])->get('v1/offers/{offer}/reviews', [OfferReviewController::class, 'index'])->name('v1.offers.reviews.index');

Route::middleware(['auth:sanctum', RequireOfferDomain::class])->prefix('v1/offers')->name('v1.offers.')->group(function () {
    Route::post('/', [OfferController::class, 'store'])->can('create', Offer::class)->name('store');
    Route::patch('{offer}', [OfferController::class, 'update'])->can('update', 'offer')->name('update');
    Route::delete('{offer}', [OfferController::class, 'destroy'])->can('delete', 'offer')->name('destroy');
    // Publier ou repasser en brouillon, depuis le menu de « Mes logements » (décision 2026-11-23-publier-depublier-menu).
    Route::post('{offer}/publish', [OfferController::class, 'publish'])->can('publish', 'offer')->name('publish');
    Route::post('{offer}/unpublish', [OfferController::class, 'unpublish'])->can('unpublish', 'offer')->name('unpublish');

    // Son avis sur une offre (décision 2026-11-19-contrat-avis-offres).
    Route::put('{offer}/review', [OfferReviewController::class, 'save'])->can('review', 'offer')->name('review.save');
    Route::delete('{offer}/review', [OfferReviewController::class, 'destroy'])->can('review', 'offer')->name('review.destroy');

    // Demander une réservation ou une visite (décision 2026-11-19-contrat-reservations).
    Route::post('{offer}/bookings', [BookingController::class, 'store'])->can('book', 'offer')->middleware('throttle:20,1')->name('bookings.store');

    // Le calendrier de son offre : un mois, un jour, bloquer et débloquer (décision 2026-11-19-contrat-calendrier-offre).
    Route::get('{offer}/calendar', [OfferCalendarController::class, 'month'])->can('update', 'offer')->name('calendar.month');
    Route::get('{offer}/calendar/{date}', [OfferCalendarController::class, 'day'])->can('update', 'offer')->where('date', '\d{4}-\d{2}-\d{2}')->name('calendar.day');
    Route::put('{offer}/blocked-dates/{date}', [OfferCalendarController::class, 'block'])->can('update', 'offer')->where('date', '\d{4}-\d{2}-\d{2}')->name('blocked-dates.save');
    Route::delete('{offer}/blocked-dates/{date}', [OfferCalendarController::class, 'unblock'])->can('update', 'offer')->where('date', '\d{4}-\d{2}-\d{2}')->name('blocked-dates.destroy');

    // Ses favoris (décision 2026-11-19-contrat-vedette-favoris).
    Route::put('{offer}/favorite', [OfferFavoriteController::class, 'save'])->can('favorite', 'offer')->name('favorite.save');
    Route::delete('{offer}/favorite', [OfferFavoriteController::class, 'destroy'])->can('favorite', 'offer')->name('favorite.destroy');
});

// La page publique d'un membre, pour un compte connecté (décision 2026-11-19-page-membre).
Route::middleware(['auth:sanctum', RequireOfferDomain::class, 'throttle:60,1'])
    ->get('v1/members/{member}', [MemberController::class, 'show'])
    ->can('viewMember', 'member')
    ->whereNumber('member')
    ->name('v1.members.show');

// Répondre à une demande (le propriétaire), l'annuler (le membre) : décision 2026-11-19-contrat-reservations.
Route::middleware(['auth:sanctum', RequireOfferDomain::class])->prefix('v1/bookings')->name('v1.bookings.')->group(function () {
    Route::post('{booking}/answer', [BookingController::class, 'answer'])->can('answer', 'booking')->name('answer');
    // Accepter d'un geste, depuis la carte de la demande (décision 2026-11-19-contrat-reservations-logements-profil).
    Route::post('{booking}/accept', [BookingController::class, 'accept'])->can('answer', 'booking')->name('accept');
    Route::post('{booking}/cancel', [BookingController::class, 'cancel'])->can('cancel', 'booking')->name('cancel');
    // Inviter le membre à donner son avis, une fois la demande terminée (décision 2026-11-22-inviter-avis-depuis-la-demande).
    Route::post('{booking}/invite-review', [BookingController::class, 'inviteReview'])->can('inviteReview', 'booking')->middleware('throttle:10,1')->name('invite-review');
    // Afficher ou masquer l'adresse du logement au demandeur (décision 2026-11-21-afficher-adresse).
    Route::post('{booking}/address/show', [BookingController::class, 'showAddress'])->can('manageAddress', 'booking')->name('address.show');
    Route::post('{booking}/address/hide', [BookingController::class, 'hideAddress'])->can('manageAddress', 'booking')->name('address.hide');
    // Modifier sa demande, tant qu'elle est en attente (décision 2026-11-21-modifier-reservation).
    Route::put('{booking}', [BookingController::class, 'update'])->can('edit', 'booking')->middleware('throttle:20,1')->name('update');
});
