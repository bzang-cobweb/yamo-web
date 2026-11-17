<?php

namespace Modules\Location\Policies;

use Filament\Facades\Filament;
use Illuminate\Auth\Access\Response;
use Modules\Auth\Enums\PermissionEnum;
use Modules\Auth\Models\User;
use Modules\Auth\Policies\Concerns\HasBeforeAbility;
use Modules\Location\Models\UserAddress;

/**
 * Adresses d'un compte (décision 2026-11-18-adresse-du-compte-modele).
 * Back-office : gérer les adresses d'un utilisateur, c'est le modifier ;
 * créer une adresse demande en plus `addresses.create`. Site et API : un compte
 * gère les siennes ; celle d'un autre répond 404, pas 403.
 */
class UserAddressPolicy
{
    use HasBeforeAbility;

    public function viewAny(User $user): bool
    {
        if (Filament::isServing()) {
            return $user->hasPermissionTo(PermissionEnum::VIEW_ADDRESSES);
        }

        return true;
    }

    public function view(User $user, UserAddress $userAddress): bool|Response
    {
        if (Filament::isServing()) {
            return $user->hasPermissionTo(PermissionEnum::VIEW_ADDRESSES);
        }

        return $this->owns($user, $userAddress);
    }

    public function create(User $user): bool
    {
        if (Filament::isServing()) {
            return $user->hasPermissionTo(PermissionEnum::CREATE_ADDRESSES);
        }

        return true;
    }

    public function update(User $user, UserAddress $userAddress): bool|Response
    {
        if (Filament::isServing()) {
            return $user->hasPermissionTo(PermissionEnum::UPDATE_ADDRESSES);
        }

        return $this->owns($user, $userAddress);
    }

    public function delete(User $user, UserAddress $userAddress): bool|Response
    {
        if (Filament::isServing()) {
            return $user->hasPermissionTo(PermissionEnum::UPDATE_ADDRESSES);
        }

        return $this->owns($user, $userAddress);
    }

    public function deleteAny(User $user): bool
    {
        if (Filament::isServing()) {
            return $user->hasPermissionTo(PermissionEnum::UPDATE_ADDRESSES);
        }

        return false;
    }

    private function owns(User $user, UserAddress $userAddress): Response
    {
        return (int) $userAddress->user_id === (int) $user->getKey() ? Response::allow() : Response::denyAsNotFound();
    }
}
