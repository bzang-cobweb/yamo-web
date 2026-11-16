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
     * Connexion : e-mail ou téléphone (format international) et mot de passe. Si les
     * identifiants sont justes, un code part vers cet e-mail ou ce téléphone ; la
     * réponse décrit le défi à valider par `POST /auth/codes/verify`, qui seul rend
     * le jeton.
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
