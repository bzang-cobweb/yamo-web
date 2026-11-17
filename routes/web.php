<?php

use App\Models\Setting;
use Illuminate\Support\Facades\Route;

// Page d'accueil : logo et description de l'application. Connexion et
// inscription n'existent que dans l'app mobile (décision
// 2026-11-19-contrat-accueil-description-regions).
Route::get('/', function () {
    return view('home', [
        'name' => (string) config('app.name'),
        'description' => Setting::current()->localizedDescription(),
    ]);
})->name('home');
