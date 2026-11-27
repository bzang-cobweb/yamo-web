<?php

namespace Modules\Auth\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Modules\Auth\Http\Requests\UserPhoneRequest;
use Modules\Auth\Http\Resources\UserResource;
use Modules\Auth\Models\User;
use Modules\Auth\Models\UserPhone;
use Modules\Auth\Services\UserPhoneService;

/**
 * Les numéros supplémentaires du compte (« Mes numéros », décision 2026-11-27-plusieurs-numeros) ; le principal reste
 * `phone`, changé par `POST /me/phone`. Le numéro d'un autre compte répond 404.
 */
class UserPhoneController
{
    /**
     * Ajouter un numéro : un code part par SMS vers ce numéro ; il n'est ajouté, vérifié, qu'une fois le code validé
     * par `POST /auth/codes/verify`, qui rend le compte.
     *
     * @response 202 array{challenge: string, channel: 'sms', destination: string}
     */
    public function store(UserPhoneRequest $request, UserPhoneService $service): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        return response()->json($service->start($user, $request->validated()), 202);
    }

    /**
     * En faire le numéro principal (connexion par SMS, codes) ; l'ancien principal rejoint les numéros supplémentaires.
     */
    public function primary(UserPhone $userPhone, UserPhoneService $service): UserResource
    {
        return new UserResource($service->makePrimary($userPhone));
    }

    /**
     * Retirer un numéro supplémentaire.
     */
    public function destroy(UserPhone $userPhone, UserPhoneService $service): Response
    {
        $service->remove($userPhone);

        return response()->noContent();
    }
}
