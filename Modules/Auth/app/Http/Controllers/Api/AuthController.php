<?php

namespace Modules\Auth\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Modules\Auth\Http\Requests\LoginRequest;
use Modules\Auth\Http\Resources\UserResource;
use Modules\Auth\Models\User;
use Modules\Auth\Services\AuthService;

/**
 * Connexion de l'application mobile par jeton Sanctum.
 */
class AuthController
{
    /**
     * Connexion : renvoie l'utilisateur et un jeton à envoyer ensuite en
     * `Authorization: Bearer <token>`.
     *
     * @unauthenticated
     */
    public function login(LoginRequest $request, AuthService $service): UserResource
    {
        ['user' => $user, 'token' => $token] = $service->login($request->validated());

        return (new UserResource($user))->additional([
            /** Jeton Sanctum de l'appareil. */
            'token' => $token,
        ]);
    }

    /**
     * Déconnexion : révoque le jeton utilisé par la requête.
     */
    public function logout(Request $request, AuthService $service): Response
    {
        /** @var User $user */
        $user = $request->user();
        $service->logout($user);

        return response()->noContent();
    }

    /**
     * Utilisateur connecté.
     */
    public function me(Request $request): UserResource
    {
        return new UserResource($request->user());
    }
}
