<?php

namespace Modules\Location\Services;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Auth\Models\User;
use Modules\Location\Models\Address;
use Modules\Location\Models\UserAddress;

/**
 * La règle des adresses d'un compte, servie à l'API comme au back-office
 * (décisions 2026-11-18-adresses-liste-generique et
 * 2026-11-18-adresse-du-compte-modele) :
 * - ajouter : l'adresse identique est reprise, ou créée, puis une `UserAddress`
 *   la vise avec le destinataire saisi ;
 * - modifier : le destinataire change sur place ; l'adresse saisie est reprise ou
 *   créée, et la `UserAddress` la vise. Une adresse partagée n'est jamais
 *   modifiée par un compte ;
 * - retirer : la `UserAddress` disparaît, l'adresse reste dans son pays.
 */
class AddressService
{
    /** Champs de l'adresse partagée. */
    public const ADDRESS_FIELDS = ['country_id', 'street', 'zip', 'city', 'latitude', 'longitude'];

    /** Longueur minimale de la rue avant de proposer des adresses existantes (back-office). */
    public const SUGGESTION_MIN_LENGTH = 3;

    /** Champs du destinataire, propres au compte. */
    public const RECIPIENT_FIELDS = ['gender', 'first_name', 'last_name'];

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
     * Ajoute au compte l'adresse saisie, avec son destinataire.
     *
     * @param  array<string, mixed>  $data
     */
    public function attach(User $user, array $data): UserAddress
    {
        return DB::transaction(function () use ($user, $data): UserAddress {
            $address = $this->findOrCreate($data);

            return $user->addresses()->create([
                'address_id' => $address->getKey(),
                ...$this->recipient($data),
            ]);
        });
    }

    /**
     * Modifie une adresse du compte : son destinataire, et l'adresse visée.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(UserAddress $userAddress, array $data): UserAddress
    {
        return DB::transaction(function () use ($userAddress, $data): UserAddress {
            $userAddress->fill([
                'address_id' => $this->findOrCreate($data)->getKey(),
                ...$this->recipient($data),
            ])->save();

            return $userAddress->refresh();
        });
    }

    /**
     * Retire l'adresse du compte ; l'adresse reste dans son pays.
     */
    public function detach(UserAddress $userAddress): void
    {
        $userAddress->delete();
    }

    /**
     * Adresses existantes dont la rue contient `$search` (sans casse), dans le
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
            ->whereRaw('LOWER(street) LIKE ?', ['%'.addcslashes($search, '%_\\').'%'])
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

    /**
     * @param  array<string, mixed>  $data
     * @return array{gender: mixed, first_name: mixed, last_name: mixed}
     */
    private function recipient(array $data): array
    {
        return [
            'gender' => $data['gender'] ?? null,
            'first_name' => $data['first_name'] ?? '',
            'last_name' => $data['last_name'] ?? '',
        ];
    }
}
