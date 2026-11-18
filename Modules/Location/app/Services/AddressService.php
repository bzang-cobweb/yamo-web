<?php

namespace Modules\Location\Services;

use App\Support\ContainsSearch;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;
use Modules\Location\Models\Address;

/**
 * L'adresse partagée, unique par pays : la retrouver, la créer, la suggérer
 * (back-office). Les adresses d'un compte sont gérées par le module Auth, qui
 * s'appuie sur ce service (décision 2026-11-18-useraddress-dans-auth).
 */
class AddressService
{
    /** Champs de l'adresse partagée. */
    public const ADDRESS_FIELDS = ['country_id', 'street', 'zip', 'city', 'latitude', 'longitude'];

    /** Longueur minimale de la rue avant de proposer des adresses existantes (back-office). */
    public const SUGGESTION_MIN_LENGTH = 3;

    /**
     * L'adresse identique à la saisie (même pays, même empreinte), ou `null`.
     *
     * @param  array<string, mixed>  $data
     */
    public function find(array $data): ?Address
    {
        return Address::query()
            ->where('country_id', $data['country_id'])
            ->where('fingerprint', Address::fingerprint((string) $data['street'], (string) $data['zip'], (string) $data['city']))
            ->first();
    }

    /**
     * L'adresse identique, ou une nouvelle. Une adresse existante garde sa
     * latitude et sa longitude.
     *
     * @param  array<string, mixed>  $data
     */
    public function findOrCreate(array $data): Address
    {
        return $this->find($data) ?? Address::query()->create([
            'country_id' => $data['country_id'],
            'street' => $data['street'],
            'zip' => $data['zip'],
            'city' => $data['city'],
            'latitude' => $data['latitude'] ?? null,
            'longitude' => $data['longitude'] ?? null,
        ]);
    }

    /**
     * Adresses existantes dont la rue contient `$search` (sans casse, `%` et `_`
     * cherchés tels quels : `ContainsSearch`), dans le
     * pays donné s'il l'est, par ordre de rue : les suggestions de la saisie.
     *
     * @return Collection<int, Address>
     */
    public function suggest(string $search, int|string|null $countryId, int $limit): Collection
    {
        $search = Str::lower(Str::squish($search));

        return Address::query()
            ->with('country')
            ->when(filled($countryId), fn ($query) => $query->where('country_id', $countryId))
            ->where(fn ($query) => ContainsSearch::orWhere($query, ['street'], $search))
            ->orderBy('street')
            ->orderBy('zip')
            ->limit($limit)
            ->get();
    }

    /**
     * L'adresse d'une suggestion choisie (« Rue du Lac 4 — 1200 Genève »), ou `null`.
     */
    public function fromSuggestionLabel(string $label, int|string|null $countryId): ?Address
    {
        return $this->suggest(Str::before($label, ' — '), $countryId, 50)
            ->first(fn (Address $address): bool => $address->suggestionLabel() === $label);
    }
}
