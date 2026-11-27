<?php

// Routes d'API du module Partner (préfixe `api`, noms `api.`) : les partenaires de l'accueil, sans compte
// (décision 2026-11-23-module-partenaires).

use Illuminate\Support\Facades\Route;
use Modules\Partner\Http\Controllers\Api\MyPartnerController;
use Modules\Partner\Http\Controllers\Api\PartnerController;
use Modules\Partner\Http\Controllers\Api\PartnerMessageController;
use Modules\Partner\Models\Partner;

// Publiques, visiteur compris : la policy `PartnerPolicy` décide (décision 2026-11-24-partenaires-policy-routes) ; la page
// et le clic l'appellent dans le contrôleur, avec le compte Sanctum s'il y en a un.
Route::middleware('throttle:60,1')->get('v1/partners', [PartnerController::class, 'index'])->can('viewAny', Partner::class)->name('v1.partners.index');
// La fiche d'un partenaire ; la page « Partenaires » est la liste `partner.all` (décision 2026-11-23-partenaires-liste-detail).
Route::middleware('throttle:60,1')->get('v1/partners/{partner}', [PartnerController::class, 'show'])->whereNumber('partner')->name('v1.partners.show');
Route::middleware('throttle:30,1')->post('v1/partners/{partner}/click', [PartnerController::class, 'click'])->whereNumber('partner')->name('v1.partners.click');

// « Mes partenaires » : un membre propose, modifie et retire ses partenaires (décision
// 2026-11-24-partenaires-par-les-membres).
Route::middleware('auth:sanctum')->prefix('v1/partners')->name('v1.partners.')->group(function () {
    Route::post('/', [MyPartnerController::class, 'store'])->can('create', Partner::class)->middleware('throttle:10,1')->name('store');
    Route::patch('{partner}', [MyPartnerController::class, 'update'])->whereNumber('partner')->can('update', 'partner')->name('update');
    Route::delete('{partner}', [MyPartnerController::class, 'destroy'])->whereNumber('partner')->can('delete', 'partner')->name('destroy');
    // Écrire aux autres membres, dans son fil (décision 2026-11-27-partenaire-messages).
    Route::post('{partner}/messages', [PartnerMessageController::class, 'store'])->whereNumber('partner')->can('message', 'partner')->middleware('throttle:20,1')->name('messages.store');
});
