<?php

namespace Modules\Auth\Http\Controllers\Api;

use Modules\Auth\Http\Requests\LoginRequest;
use Modules\Auth\Http\Resources\UserResource;
use Modules\Auth\Services\LoginService;

/**
 * Connexion de l'application mobile par jeton Sanctum.
 */
class LoginController
{
    /**
     * Connexion : renvoie l'utilisateur et un jeton à envoyer ensuite en
     * `Authorization: Bearer <token>`.
     *
     * @unauthenticated
     */
    public function login(LoginRequest $request, LoginService $service): UserResource
    {
        ['user' => $user, 'token' => $token] = $service->login($request->validated());

        return (new UserResource($user))->additional([
            /** Jeton Sanctum de l'appareil. */
            'token' => $token,
        ]);
    }
}
