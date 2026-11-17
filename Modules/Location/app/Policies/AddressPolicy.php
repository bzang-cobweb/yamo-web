<?php

namespace Modules\Location\Policies;

use Filament\Facades\Filament;
use Modules\Auth\Enums\PermissionEnum;
use Modules\Auth\Models\User;
use Modules\Auth\Policies\Concerns\HasBeforeAbility;
use Modules\Location\Models\Address;

/**
 * Adresses partagées d'un pays, gérées dans le back-office seul (permissions
 * `addresses.*`). Site et API : un compte ne gère que ses `UserAddress`
 * (`UserAddressPolicy`, décisions 2026-11-18-adresse-du-compte-modele et
 * 2026-11-18-adresse-app-simplifiee).
 */
class AddressPolicy
{
    use HasBeforeAbility;

    public function viewAny(User $user): bool
    {
        return Filament::isServing() && $user->hasPermissionTo(PermissionEnum::VIEW_ADDRESSES);
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
