<?php

// Routes d'API du module Partner (préfixe `api`, noms `api.`) : les partenaires de l'accueil, sans compte
// (décision 2026-11-23-module-partenaires).

use Illuminate\Support\Facades\Route;
use Modules\Partner\Http\Controllers\Api\PartnerController;

Route::middleware('throttle:60,1')->get('v1/partners', [PartnerController::class, 'index'])->name('v1.partners.index');
// La fiche d'un partenaire ; la page « Partenaires » est la liste `partner.all` (décision 2026-11-23-partenaires-liste-detail).
Route::middleware('throttle:60,1')->get('v1/partners/{partner}', [PartnerController::class, 'show'])->whereNumber('partner')->name('v1.partners.show');
Route::middleware('throttle:30,1')->post('v1/partners/{partner}/click', [PartnerController::class, 'click'])->whereNumber('partner')->name('v1.partners.click');
