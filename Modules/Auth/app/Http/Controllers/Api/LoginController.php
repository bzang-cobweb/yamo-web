<?php

namespace Modules\Auth\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Modules\Auth\Http\Requests\LoginRequest;
use Modules\Auth\Services\LoginService;

/**
 * Connexion de l'application mobile, première étape (formulaire `auth.login`).
 */
class LoginController
{
    /**
     * Se connecter ou s'inscrire : e-mail ou téléphone (et son pays). Un code part toujours vers cet identifiant, compte
     * connu ou non, et la réponse a la même forme (202) : l'existence du compte n'est révélée qu'après le code, par
     * `POST /auth/codes/verify` (`{next: "password", challenge, mode}`). Identifiant invalide : 422.
     *
     * @unauthenticated
     *
     * @response 202 array{challenge: string, channel: 'email'|'sms', destination: string}
     */
    public function login(LoginRequest $request, LoginService $service): JsonResponse
    {
        return response()->json($service->start($request->validated()), 202);
    }
}
