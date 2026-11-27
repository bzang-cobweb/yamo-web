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
     * Connexion : e-mail ou téléphone, sans mot de passe. Compte trouvé : un code part
     * vers cet e-mail ou ce téléphone ; la réponse (202) décrit le défi à valider par
     * `POST /auth/codes/verify`, qui seul rend le jeton. Compte inconnu (200) :
     * `next: register` et l'identifiant, à renvoyer avec l'inscription.
     *
     * @unauthenticated
     *
     * @response 202 array{challenge: string, channel: 'email'|'sms', destination: string}
     * @response 200 array{next: 'register', values: array<string, string>}
     */
    public function login(LoginRequest $request, LoginService $service): JsonResponse
    {
        $result = $service->start($request->validated());

        return response()->json($result, isset($result['next']) ? 200 : 202);
    }
}
