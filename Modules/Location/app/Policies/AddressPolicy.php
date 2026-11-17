<?php

namespace Modules\Location\Policies;

use Filament\Facades\Filament;
use Illuminate\Auth\Access\Response;
use Modules\Auth\Enums\PermissionEnum;
use Modules\Auth\Models\User;
use Modules\Auth\Policies\Concerns\HasBeforeAbility;
use Modules\Location\Models\Address;

/**
 * Back-office : permissions `addresses.*`. Site et API : un compte gère ses
 * propres adresses, c'est-à-dire celles qui lui sont rattachées ; celle d'un
 * autre répond 404 (décision
 * 2026-11-18-adresses-liste-generique).
 */
class AddressPolicy
{
    use HasBeforeAbility;

    public function viewAny(User $user): bool
    {
        if (Filament::isServing()) {
            return $user->hasPermissionTo(PermissionEnum::VIEW_ADDRESSES);
        }

        return true;
    }

    public function view(User $user, Address $address): bool|Response
    {
        if (Filament::isServing()) {
            return $user->hasPermissionTo(PermissionEnum::VIEW_ADDRESSES);
        }

        return $this->owns($user, $address);
    }

    public function create(User $user): bool
    {
        if (Filament::isServing()) {
            return $user->hasPermissionTo(PermissionEnum::CREATE_ADDRESSES);
        }

        return true;
    }

    public function update(User $user, Address $address): bool|Response
    {
        if (Filament::isServing()) {
            return $user->hasPermissionTo(PermissionEnum::UPDATE_ADDRESSES);
        }

        return $this->owns($user, $address);
    }

    public function delete(User $user, Address $address): bool
    {
        if (Filament::isServing()) {
            return $user->hasPermissionTo(PermissionEnum::DELETE_ADDRESSES);
        }

        // Site et API : un compte retire une adresse, il ne la supprime pas.
        return false;
    }

    public function deleteAny(User $user): bool
    {
        if (Filament::isServing()) {
            return $user->hasPermissionTo(PermissionEnum::DELETE_ADDRESSES);
        }

        return false;
    }

    /**
     * Retirer une adresse d'un compte : c'est modifier ses adresses.
     */
    public function detach(User $user, Address $address): bool|Response
    {
        if (Filament::isServing()) {
            return $user->hasPermissionTo(PermissionEnum::UPDATE_ADDRESSES);
        }

        return $this->owns($user, $address);
    }

    public function detachAny(User $user): bool
    {
        if (Filament::isServing()) {
            return $user->hasPermissionTo(PermissionEnum::UPDATE_ADDRESSES);
        }

        return false;
    }

    /**
     * L'adresse d'un autre compte n'existe pas pour lui : 404, pas 403.
     */
    private function owns(User $user, Address $address): Response
    {
        return $user->addresses()->whereKey($address->getKey())->exists()
            ? Response::allow()
            : Response::denyAsNotFound();
    }
}
