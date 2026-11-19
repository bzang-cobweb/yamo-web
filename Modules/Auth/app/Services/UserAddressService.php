<?php

namespace Modules\Auth\Services;

use Illuminate\Support\Facades\DB;
use Modules\Auth\Models\User;
use Modules\Auth\Models\UserAddress;
use Modules\Location\Services\AddressService;

/**
 * La règle des adresses d'un compte, servie à l'API comme au back-office
 * (décisions 2026-11-18-adresse-du-compte-modele et
 * 2026-11-18-useraddress-dans-auth). L'adresse partagée vient d'`AddressService`
 * (module Location) :
 * - ajouter : l'adresse identique est reprise, ou créée, puis une `UserAddress`
 *   la vise avec le destinataire saisi ;
 * - modifier : le destinataire change sur place ; l'adresse saisie est reprise ou
 *   créée, et la `UserAddress` la vise. Une adresse partagée n'est jamais
 *   modifiée par un compte ;
 * - retirer : la `UserAddress` disparaît, l'adresse reste dans son pays.
 */
class UserAddressService
{
    /** Champs du destinataire, propres au compte. */
    public const RECIPIENT_FIELDS = ['gender', 'first_name', 'last_name'];

    public function __construct(private readonly AddressService $addressService) {}

    /**
     * Ajoute au compte l'adresse saisie, avec son destinataire.
     *
     * @param  array<string, mixed>  $data
     */
    public function attach(User $user, array $data): UserAddress
    {
        return DB::transaction(function () use ($user, $data): UserAddress {
            $address = $this->addressService->findOrCreate($data);

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
                'address_id' => $this->addressService->findOrCreate($data)->getKey(),
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
     * @param  array<string, mixed>  $data
     * @return array{gender: mixed, first_name: mixed, last_name: mixed, indication: string|null}
     */
    private function recipient(array $data): array
    {
        $indication = trim((string) ($data['indication'] ?? ''));

        return [
            'gender' => $data['gender'] ?? null,
            'first_name' => $data['first_name'] ?? '',
            'last_name' => $data['last_name'] ?? '',
            'indication' => $indication === '' ? null : $indication,
        ];
    }
}
