<?php

namespace Modules\Location\Policies;

use Filament\Facades\Filament;
use Modules\Auth\Enums\PermissionEnum;
use Modules\Auth\Models\User;
use Modules\Auth\Policies\Concerns\HasBeforeAbility;
use Modules\Location\Models\Address;

/**
 * Adresses partagées d'un pays. Back-office : permissions `addresses.*`. Site et
 * API : un compte lit les suggestions d'adresses existantes, mais ne gère que
 * ses `UserAddress` (`UserAddressPolicy`, décision
 * 2026-11-18-adresse-du-compte-modele).
 */
class AddressPolicy
{
    use HasBeforeAbility;

    public function viewAny(User $user): bool
    {
        if (Filament::isServing()) {
            return $user->hasPermissionTo(PermissionEnum::VIEW_ADDRESSES);
        }

        // Site et API : suggestions de la saisie d'une adresse (rue, NPA, ville).
        return true;
    }

    public function view(User $user, Address $address): bool
    {
        return Filament::isServing() && $user->hasPermissionTo(PermissionEnum::VIEW_ADDRESSES);
    }

    public function create(User $user): bool
    {
        return Filament::isServing() && $user->hasPermissionTo(PermissionEnum::CREATE_ADDRESSES);
    }

    public function update(User $user, Address $address): bool
    {
        return Filament::isServing() && $user->hasPermissionTo(PermissionEnum::UPDATE_ADDRESSES);
    }

    public function delete(User $user, Address $address): bool
    {
        return Filament::isServing() && $user->hasPermissionTo(PermissionEnum::DELETE_ADDRESSES);
    }

    public function deleteAny(User $user): bool
    {
        return Filament::isServing() && $user->hasPermissionTo(PermissionEnum::DELETE_ADDRESSES);
    }
}
