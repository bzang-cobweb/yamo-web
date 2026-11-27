<?php

use Illuminate\Support\Facades\Route;
use Bza\Form\Http\Controllers\Api\FormController;
use Bza\Form\Http\Controllers\Api\FormStepController;
use Bza\Form\Http\Controllers\Api\FormUploadController;
use Bza\Form\Http\Controllers\Api\ListingController;

// API routes of the form package (`api` prefix, `api.` names). `{form}` is resolved
// by the registry (404 if unknown); access is decided by the form
// (`viewForm` ability: 401 for a guest, 403 for a refused account).

Route::get('v1/forms/{form}', [FormController::class, 'show'])
    ->can('viewForm', 'form')
    ->name('v1.forms.show');

Route::post('v1/forms/{form}/steps/{step}', [FormStepController::class, 'validate'])
    ->whereNumber('step')
    ->can('viewForm', 'form')
    ->middleware('throttle:30,1')
    ->name('v1.forms.steps.validate');

// Files of an `upload` field, sent one by one before saving (decision
// 2026-11-19-contrat-champ-upload-photos): restricted to a signed-in account.
Route::post('v1/forms/{form}/uploads/{field}', [FormUploadController::class, 'store'])
    ->middleware(['auth:sanctum', 'throttle:60,1'])
    ->can('viewForm', 'form')
    ->name('v1.forms.uploads.store');

// Lists displayed by the app (decision 2026-11-18-adresses-liste-generique).
// `{list}` is resolved by the registry (unknown: 404); `viewList` ability.
Route::get('v1/lists/{list}', [ListingController::class, 'show'])
    ->can('viewList', 'list')
    ->name('v1.lists.show');
