<?php

use Illuminate\Support\Facades\Route;
use Modules\Form\Http\Controllers\Api\FormController;
use Modules\Form\Http\Controllers\Api\FormStepController;

// Routes d'API du module Form (préfixe `api`, noms `api.`). `{form}` est résolu
// par le registre (404 si inconnu) ; l'accès est décidé par le formulaire
// (capacité `viewForm` : 401 pour un invité, 403 pour un compte refusé).

Route::get('v1/forms/{form}', [FormController::class, 'show'])
    ->can('viewForm', 'form')
    ->name('v1.forms.show');

Route::post('v1/forms/{form}/steps/{step}', [FormStepController::class, 'validate'])
    ->whereNumber('step')
    ->can('viewForm', 'form')
    ->middleware('throttle:30,1')
    ->name('v1.forms.steps.validate');
