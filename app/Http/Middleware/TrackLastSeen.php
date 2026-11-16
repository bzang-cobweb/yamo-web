<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Modules\Auth\Models\User;
use Symfony\Component\HttpFoundation\Response;

/**
 * Dernière activité d'un compte connecté par jeton (statut « en ligne »). Écrite
 * au plus une fois par minute, sans toucher `updated_at`.
 */
class TrackLastSeen
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user('sanctum');

        if ($user instanceof User && ($user->last_seen_at === null || $user->last_seen_at->lt(now()->subMinute()))) {
            $now = now();
            User::query()->whereKey($user->getKey())->toBase()->update(['last_seen_at' => $now]);
            $user->setAttribute('last_seen_at', $now)->syncOriginalAttribute('last_seen_at');
        }

        return $next($request);
    }
}
