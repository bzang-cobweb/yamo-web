<?php

use App\Http\Middleware\SetSessionLocale;
use App\Models\Setting;
use Bza\Form\Support\Locales;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Page d'accueil : logo et description de l'application. Connexion et
// inscription n'existent que dans l'app mobile (décision
// 2026-11-19-contrat-accueil-description-regions).
Route::middleware(SetSessionLocale::class)->group(function (): void {
    Route::get('/', function () {
        return view('home', [
            'name' => (string) config('app.name'),
            'description' => Setting::current()->localizedDescription(),
            'locales' => Locales::options(),
        ]);
    })->name('home');

    // Choix de la langue par le visiteur, gardé en session (décision
    // 2026-11-20-contrat-accueil-nom-langue).
    // Formulaire de la liste déroulante : `GET /locale?locale=en`, sans JavaScript.
    Route::get('/locale', function (Request $request) {
        $locale = $request->query('locale');
        abort_unless(is_string($locale) && in_array($locale, Locales::values(), true), 404);

        $request->session()->put(SetSessionLocale::SESSION_KEY, $locale);

        return redirect()->route('home');
    })->name('locale.switch');
});
