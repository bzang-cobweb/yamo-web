<?php

namespace Modules\Location\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Modules\Auth\Models\User;
use Modules\Location\Http\Requests\AddressRequest;
use Modules\Location\Models\UserAddress;
use Modules\Location\Services\AddressService;

/**
 * Les adresses du compte, selon la règle d'`AddressService` (décisions
 * 2026-11-18-adresses-liste-generique et 2026-11-18-adresse-du-compte-modele).
 * L'adresse d'un autre compte répond 404. Les réponses sont vides : l'app
 * recharge la liste.
 */
class AddressController
{
    /**
     * Ajouter une adresse avec son destinataire : l'adresse identique existante
     * est reprise, sinon elle est créée.
     */
    public function store(AddressRequest $request, AddressService $service): Response
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
    public function update(AddressRequest $request, UserAddress $userAddress, AddressService $service): Response
    {
        $service->update($userAddress, $request->validated());

        return response()->noContent();
    }

    /**
     * Retirer une de ses adresses ; l'adresse reste dans son pays.
     */
    public function destroy(Request $request, UserAddress $userAddress, AddressService $service): Response
    {
        $service->detach($userAddress);

        return response()->noContent();
    }
}
