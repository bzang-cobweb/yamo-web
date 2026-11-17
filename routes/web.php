<?php

use App\Models\Setting;
use Illuminate\Support\Facades\Route;

// Page d'accueil : logo (ou nom) et description de l'application. Connexion et
// inscription n'existent que dans l'app mobile (décision
// 2026-11-19-contrat-accueil-description-regions).
Route::get('/', function () {
    $setting = Setting::current();

    return view('home', [
        'name' => (string) config('app.name'),
        'logoUrl' => $setting->logoUrl(),
        'description' => $setting->localizedDescription(),
    ]);
})->name('home');
