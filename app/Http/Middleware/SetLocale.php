<?php

namespace App\Http\Middleware;

use Bza\Form\Support\Locales;
use Closure;
use Illuminate\Contracts\Translation\HasLocalePreference;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

/**
 * Langue des réponses de l'API : celle du compte connecté par jeton, sinon
 * l'en-tête `Accept-Language` de l'appareil, sinon la langue par défaut.
 */
class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user('sanctum');

        $locale = $user instanceof HasLocalePreference
            ? $user->preferredLocale()
            : $request->getPreferredLanguage(Locales::values());

        App::setLocale(in_array($locale, Locales::values(), true) ? $locale : Locales::default());

        return $next($request);
    }
}
