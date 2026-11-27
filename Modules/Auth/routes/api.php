<?php

use Illuminate\Support\Facades\Route;
use Modules\Auth\Http\Controllers\Api\AccountController;
use Modules\Auth\Http\Controllers\Api\EmailChangeController;
use Modules\Auth\Http\Controllers\Api\IdentityController;
use Modules\Auth\Http\Controllers\Api\LoginController;
use Modules\Auth\Http\Controllers\Api\LogoutController;
use Modules\Auth\Http\Controllers\Api\NotificationSettingsController;
use Modules\Auth\Http\Controllers\Api\PasswordController;
use Modules\Auth\Http\Controllers\Api\PasswordResetController;
use Modules\Auth\Http\Controllers\Api\PersonalInfoController;
use Modules\Auth\Http\Controllers\Api\PhoneChangeController;
use Modules\Auth\Http\Controllers\Api\PreferencesController;
use Modules\Auth\Http\Controllers\Api\ProfileController;
use Modules\Auth\Http\Controllers\Api\ProfileMenuController;
use Modules\Auth\Http\Controllers\Api\RegisterController;
use Modules\Auth\Http\Controllers\Api\ResidenceController;
use Modules\Auth\Http\Controllers\Api\UserAddressController;
use Modules\Auth\Http\Controllers\Api\VerificationController;
use Modules\Auth\Models\User;
use Modules\Auth\Models\UserAddress;

// Routes d'API du module Auth (préfixe `api`, noms `api.`) : chaque route qui agit
// sur un modèle porte la garde `can:` de sa policy (bza-laravel:create-policy).

Route::prefix('v1/auth')->name('v1.auth.')->group(function () {
    Route::post('login', [LoginController::class, 'login'])
        ->middleware('throttle:auth-login')
        ->name('login');
    Route::post('register', [RegisterController::class, 'register'])
        ->middleware('throttle:auth-register')
        ->name('register');
    Route::post('password/forgot', [PasswordResetController::class, 'forgot'])
        ->middleware('throttle:auth-login')
        ->name('password.forgot');
    Route::post('account/restore', [AccountController::class, 'restore'])
        ->middleware('throttle:auth-codes')
        ->name('account.restore');
    Route::post('password/reset', [PasswordResetController::class, 'reset'])
        ->middleware('throttle:auth-codes')
        ->name('password.reset');
    Route::post('codes/verify', [VerificationController::class, 'verify'])
        ->middleware('throttle:auth-codes')
        ->name('codes.verify');
    Route::post('codes/resend', [VerificationController::class, 'resend'])
        ->middleware('throttle:auth-codes')
        ->name('codes.resend');

    // L'aperçu d'une photo d'identité, par un lien signé de quelques minutes (décision 2026-11-25-identite-plusieurs-photos).
    Route::get('identity-documents/{uuid}', [IdentityController::class, 'show'])
        ->middleware('signed:relative')
        ->name('identity-documents.show');

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('logout', [LogoutController::class, 'logout'])->name('logout');
        Route::get('me', [ProfileController::class, 'show'])->can('me', User::class)->name('me');
        Route::get('me/menu', [ProfileMenuController::class, 'index'])->can('me', User::class)->name('me.menu');
        Route::get('me/menu/{menu}', [ProfileMenuController::class, 'show'])->can('me', User::class)->name('me.menu.show');
        Route::delete('me', [AccountController::class, 'destroy'])->can('deleteMe', User::class)->name('me.destroy');
        Route::patch('me/personal-info', [PersonalInfoController::class, 'update'])->can('updateMe', User::class)->name('me.personal-info.update');
        Route::put('me/password', [PasswordController::class, 'update'])->can('updateMe', User::class)->name('me.password.update');
        Route::post('me/identity', [IdentityController::class, 'update'])->can('updateMe', User::class)->name('me.identity.update');
        Route::patch('me/preferences', [PreferencesController::class, 'update'])->can('updateMe', User::class)->name('me.preferences.update');
        Route::patch('me/notifications', [NotificationSettingsController::class, 'update'])->can('updateMe', User::class)->name('me.notifications.update');
        Route::put('me/residence', [ResidenceController::class, 'update'])->can('updateMe', User::class)->name('me.residence.update');
        Route::post('me/avatar', [ProfileController::class, 'updateAvatar'])->can('updateMe', User::class)->name('me.avatar.update');
        Route::delete('me/avatar', [ProfileController::class, 'destroyAvatar'])->can('updateMe', User::class)->name('me.avatar.destroy');
        Route::post('me/email', [EmailChangeController::class, 'store'])->can('updateMe', User::class)->middleware('throttle:auth-codes')->name('me.email.store');
        Route::post('me/phone', [PhoneChangeController::class, 'store'])->can('updateMe', User::class)->middleware('throttle:auth-codes')->name('me.phone.store');

        // Adresses du compte (décisions 2026-11-18-adresse-du-compte-modele et
        // 2026-11-18-useraddress-dans-auth). Celle d'un autre compte répond 404
        // (`UserAddressPolicy`).
        Route::post('me/addresses', [UserAddressController::class, 'store'])->can('create', UserAddress::class)->name('me.addresses.store');
        Route::patch('me/addresses/{userAddress}', [UserAddressController::class, 'update'])->can('update', 'userAddress')->name('me.addresses.update');
        Route::delete('me/addresses/{userAddress}', [UserAddressController::class, 'destroy'])->can('delete', 'userAddress')->name('me.addresses.destroy');
    });
});
