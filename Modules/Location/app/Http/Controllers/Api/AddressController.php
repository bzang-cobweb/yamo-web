<?php

namespace Modules\Location\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Modules\Auth\Models\User;
use Modules\Location\Http\Requests\AddressRequest;
use Modules\Location\Models\Address;
use Modules\Location\Services\AddressService;

/**
 * Les adresses du compte, selon la règle d'`AddressService` (décision
 * 2026-11-18-adresses-liste-generique). L'adresse d'un autre compte répond 404.
 * Les réponses sont vides : l'app recharge la liste.
 */
class AddressController
{
    /**
     * Ajouter une adresse : l'adresse identique existante est reprise, sinon
     * elle est créée, puis rattachée au compte.
     */
    public function store(AddressRequest $request, AddressService $service): Response
    {
        $service->attach($this->user($request), $request->validated());

        return response()->noContent();
    }

    /**
     * Changer une de ses adresses : la nouvelle saisie est reprise ou créée, et
     * remplace l'ancienne pour ce compte seul. L'ancienne n'est pas modifiée.
     */
    public function update(AddressRequest $request, Address $address, AddressService $service): Response
    {
        $service->replace($this->user($request), $address, $request->validated());

        return response()->noContent();
    }

    /**
     * Retirer une de ses adresses ; elle reste dans son pays.
     */
    public function destroy(Request $request, Address $address, AddressService $service): Response
    {
        $service->detach($this->user($request), $address);

        return response()->noContent();
    }

    private function user(Request $request): User
    {
        /** @var User */
        return $request->user();
    }
}
