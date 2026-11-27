<?php

namespace App\Http\Middleware;

use Bza\Form\Support\Locales;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

/**
 * Langue des pages web publiques : celle choisie par le visiteur (gardée en
 * session), sinon celle de son navigateur, sinon la langue par défaut.
 */
class SetSessionLocale
{
    public const SESSION_KEY = 'locale';

    public function handle(Request $request, Closure $next): Response
    {
        $locale = $request->session()->get(self::SESSION_KEY) ?? $request->getPreferredLanguage(Locales::values());

        App::setLocale(in_array($locale, Locales::values(), true) ? $locale : Locales::default());

        return $next($request);
    }
}
