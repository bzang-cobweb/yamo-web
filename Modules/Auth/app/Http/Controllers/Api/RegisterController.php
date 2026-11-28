<?php

namespace Modules\Auth\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Modules\Auth\Http\Requests\RegisterRequest;
use Modules\Auth\Http\Resources\UserResource;
use Modules\Auth\Services\RegisterService;

/**
 * Créer son compte depuis l'app, dernière étape du parcours (formulaire `auth.register`, décision
 * 2026-11-28-connexion-panneau).
 */
class RegisterController
{
    /**
     * Inscription : pour un défi d'inscription dont le code a été validé et le mot de passe choisi
     * (`POST /auth/session`), le prénom, le nom et l'autre moyen de contact, facultatif (le téléphone et son pays pour
     * une inscription par e-mail, l'e-mail pour une inscription par téléphone). Le compte est créé (identifiant
     * vérifié, autre contact non vérifié, pseudonyme tiré du prénom et du nom, rôle membre) et l'appareil connecté.
     * Défi inconnu, expiré ou sans mot de passe : 422 sur `code`.
     *
     * @unauthenticated
     *
     * @status 201
     *
     * @response 201 array{data: UserResource, token: string}
     */
    public function register(RegisterRequest $request, RegisterService $service): JsonResponse
    {
        $result = $service->register($request->validated(), (string) ($request->input('device_name') ?: 'app'));

        return (new UserResource($result['user']))->additional([
            /** Jeton Sanctum de l'appareil. */
            'token' => $result['token'],
        ])->response()->setStatusCode(201);
    }
}
