<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Sur l'API, l'utilisateur se reconnaît à son jeton Sanctum, même sur une route
 * ouverte aux invités : les capacités (`can:`) et les policies reçoivent alors le
 * compte connecté s'il y en a un, `null` sinon. N'impose aucune connexion — c'est
 * le rôle de `auth:sanctum`.
 */
class UseSanctumGuard
{
    public function handle(Request $request, Closure $next): Response
    {
        Auth::shouldUse('sanctum');

        return $next($request);
    }
}
