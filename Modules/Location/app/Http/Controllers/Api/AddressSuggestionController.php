<?php

namespace Modules\Location\Http\Controllers\Api;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Str;
use Modules\Location\Http\Resources\AddressSuggestionResource;
use Modules\Location\Services\AddressService;
use Modules\Location\Support\CurrentCountry;

/**
 * Suggestions d'adresses pendant la saisie d'une rue (décision
 * 2026-11-19-contrat-adresse-offre-recherche) : les adresses existantes du pays
 * courant dont la rue contient le texte tapé. Risque assumé : toutes les adresses,
 * y compris celles des carnets des membres ; seules la rue, le code postal et la
 * ville sont servis, jamais une personne.
 */
class AddressSuggestionController
{
    /** Caractères à taper avant la moindre suggestion, comme au back-office. */
    public const MIN_LENGTH = AddressService::SUGGESTION_MIN_LENGTH;

    public const LIMIT = 10;

    /**
     * Au plus 10 adresses (une adresse est unique par pays), par ordre de rue ; rien
     * sous 3 caractères. Chaque suggestion remplit le code postal et la ville.
     */
    public function index(Request $request, AddressService $service): AnonymousResourceCollection
    {
        $request->validate([
            /** Le début ou un morceau de la rue. @example Njo */
            'q' => ['nullable', 'string', 'max:255'],
        ]);
        $term = Str::squish((string) $request->query('q'));

        // Rien sous 3 caractères ; sinon la même recherche que le back-office (`AddressService::suggest()`).
        $addresses = mb_strlen($term) < self::MIN_LENGTH
            ? new Collection
            : $service->suggest($term, CurrentCountry::require()->getKey(), self::LIMIT);

        return AddressSuggestionResource::collection($addresses);
    }
}
