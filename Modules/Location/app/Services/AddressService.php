<?php

namespace Modules\Location\Services;

use Illuminate\Support\Facades\DB;
use Modules\Auth\Models\User;
use Modules\Location\Models\Address;

/**
 * La règle des adresses d'un compte, servie à l'API comme au back-office
 * (décision 2026-11-18-adresses-liste-generique) :
 * - rattacher : l'adresse identique est reprise, ou créée, puis reliée au compte ;
 * - changer : idem pour la nouvelle saisie, et le pivot passe de l'ancienne à
 *   elle ; une adresse partagée n'est jamais modifiée par un compte ;
 * - retirer : seul le pivot disparaît, l'adresse reste dans son pays.
 *
 * @phpstan-type AddressData array{country_id: int|string, street: string, zip: string, city: string, latitude?: float|string|null, longitude?: float|string|null}
 */
class AddressService
{
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
     * Rattache la saisie au compte et renvoie l'adresse rattachée.
     *
     * @param  array<string, mixed>  $data
     */
    public function attach(User $user, array $data): Address
    {
        return DB::transaction(function () use ($user, $data): Address {
            $address = $this->findOrCreate($data);
            $user->addresses()->syncWithoutDetaching([$address->getKey()]);

            return $address;
        });
    }

    /**
     * Remplace l'adresse `$old` du compte par la saisie et renvoie la nouvelle.
     *
     * @param  array<string, mixed>  $data
     */
    public function replace(User $user, Address $old, array $data): Address
    {
        return DB::transaction(function () use ($user, $old, $data): Address {
            $address = $this->findOrCreate($data);

            if (! $address->is($old)) {
                $user->addresses()->detach($old->getKey());
                $user->addresses()->syncWithoutDetaching([$address->getKey()]);
            }

            return $address;
        });
    }

    /**
     * Retire l'adresse du compte ; l'adresse reste dans son pays.
     */
    public function detach(User $user, Address $address): void
    {
        $user->addresses()->detach($address->getKey());
    }
}
