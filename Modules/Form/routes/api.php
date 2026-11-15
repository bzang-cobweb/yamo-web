<?php

use Illuminate\Support\Facades\Route;
use Modules\Form\Http\Controllers\Api\FormController;

// Routes d'API du module Form (préfixe `api`, noms `api.`). `{form}` est résolu
// par le registre (404 si inconnu) ; l'accès est décidé par le formulaire
// (capacité `viewForm` : 401 pour un invité, 403 pour un compte refusé).

Route::get('v1/forms/{form}', [FormController::class, 'show'])
    ->can('viewForm', 'form')
    ->name('v1.forms.show');
