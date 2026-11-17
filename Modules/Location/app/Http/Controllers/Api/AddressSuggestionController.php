<?php

namespace Modules\Location\Http\Controllers\Api;

use Illuminate\Http\Request;
use Modules\Location\Models\Address;
use Modules\Location\Services\AddressService;

/**
 * Suggestions d'adresses existantes pendant la saisie de la rue (décision
 * 2026-11-18-adresse-du-compte-modele).
 */
class AddressSuggestionController
{
    /** Nombre maximal de suggestions. */
    public const LIMIT = 5;

    /** Longueur minimale de la recherche. */
    public const MIN_LENGTH = 3;

    /**
     * Adresses existantes dont la rue contient `q`, dans le pays `country_id`
     * s'il est donné : au plus 5, avec pays, rue, NPA et ville, sans coordonnées
     * ni destinataire. Choisir une suggestion remplit ces champs.
     *
     * @response array{data: list<array{label: string, values: array{country_id: string, street: string, zip: string, city: string}}>}
     */
    public function index(Request $request, AddressService $service): array
    {
        $validated = $request->validate([
            /** Début ou partie de la rue. @example rue du lac */
            'q' => ['required', 'string', 'min:'.self::MIN_LENGTH, 'max:255'],
            /** Pays choisi dans le formulaire. @example 75 */
            'country_id' => ['nullable', 'integer'],
        ]);

        return ['data' => $service->suggest($validated['q'], $validated['country_id'] ?? null, self::LIMIT)
            ->map(fn (Address $address): array => [
                'label' => $address->suggestionLabel(),
                'values' => [
                    'country_id' => (string) $address->country_id,
                    'street' => $address->street,
                    'zip' => $address->zip,
                    'city' => $address->city,
                ],
            ])
            ->values()
            ->all()];
    }
}
