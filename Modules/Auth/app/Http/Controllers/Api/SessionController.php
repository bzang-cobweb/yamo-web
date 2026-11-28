<?php

namespace Modules\Auth\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Modules\Auth\Http\Requests\SessionRequest;
use Modules\Auth\Http\Resources\UserResource;
use Modules\Auth\Services\SessionService;

/**
 * L'étape du mot de passe, après le code de connexion ou d'inscription (formulaires `auth.session.password` et
 * `auth.session.new-password`, décision 2026-11-28-connexion-panneau).
 */
class SessionController
{
    /**
     * Mot de passe, pour un défi dont le code a été validé il y a moins de 30 minutes, selon le `mode` rendu par
     * `POST /auth/codes/verify` :
     * - `sign-in` : le mot de passe du compte → l'utilisateur et son jeton (défi clos) ; faux : 422 sur `password`,
     *   et au cinquième essai faux le défi est clos ; avec `reset: true`, un nouveau mot de passe confirmé
     *   (« Mot de passe oublié ? ») → enregistré, les autres appareils déconnectés, l'utilisateur et son jeton ;
     * - `create` : un nouveau mot de passe confirmé → enregistré, l'utilisateur et son jeton ;
     * - `register` : un mot de passe confirmé, gardé haché avec le défi → `{next: "register", challenge}`, puis
     *   `POST /auth/register`.
     * Défi inconnu, déjà servi, au code non validé ou expiré : 422 sur `code`.
     *
     * @unauthenticated
     *
     * @response array{data: UserResource, token: string}
     * @response array{next: 'register', challenge: string}
     */
    public function store(SessionRequest $request, SessionService $service): UserResource|JsonResponse
    {
        $result = $service->open($request->validated(), (string) ($request->input('device_name') ?: 'app'));

        if (isset($result['next'])) {
            return response()->json($result);
        }

        return (new UserResource($result['user']))->additional([
            /** Jeton Sanctum de l'appareil. */
            'token' => $result['token'],
        ]);
    }
}
