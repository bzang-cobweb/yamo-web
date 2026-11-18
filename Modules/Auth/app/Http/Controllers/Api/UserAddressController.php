<?php

namespace Modules\Auth\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Modules\Auth\Http\Requests\UserAddressRequest;
use Modules\Auth\Models\User;
use Modules\Auth\Models\UserAddress;
use Modules\Auth\Services\UserAddressService;

/**
 * Les adresses du compte, selon la règle d'`UserAddressService` (décisions
 * 2026-11-18-adresse-du-compte-modele et 2026-11-18-useraddress-dans-auth).
 * L'adresse d'un autre compte répond 404. Les réponses sont vides : l'app
 * recharge la liste.
 */
class UserAddressController
{
    /**
     * Ajouter une adresse avec son destinataire : l'adresse identique existante
     * est reprise, sinon elle est créée.
     */
    public function store(UserAddressRequest $request, UserAddressService $service): Response
    {
        /** @var User $user */
        $user = $request->user();
        $service->attach($user, $request->validated());

        return response()->noContent();
    }

    /**
     * Modifier une de ses adresses : le destinataire change sur place ; l'adresse
     * saisie est reprise ou créée. L'adresse précédente n'est pas modifiée.
     */
    public function update(UserAddressRequest $request, UserAddress $userAddress, UserAddressService $service): Response
    {
        $service->update($userAddress, $request->validated());

        return response()->noContent();
    }

    /**
     * Retirer une de ses adresses ; l'adresse reste dans son pays.
     */
    public function destroy(Request $request, UserAddress $userAddress, UserAddressService $service): Response
    {
        $service->detach($userAddress);

        return response()->noContent();
    }
}
