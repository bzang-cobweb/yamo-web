<?php

use App\Http\Middleware\SetSessionLocale;
use App\Models\Setting;
use App\Support\Locales;
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
    Route::get('/locale/{locale}', function (Request $request, string $locale) {
        $request->session()->put(SetSessionLocale::SESSION_KEY, $locale);

        return redirect()->route('home');
    })->whereIn('locale', Locales::values())->name('locale.switch');
});
