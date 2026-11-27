<?php

namespace Modules\Auth\Policies;

use Filament\Facades\Filament;
use Illuminate\Auth\Access\Response;
use Modules\Auth\Enums\PermissionEnum;
use Modules\Auth\Models\User;
use Modules\Auth\Models\UserPhone;
use Modules\Auth\Policies\Concerns\HasBeforeAbility;

/**
 * Numéros supplémentaires d'un compte (décision 2026-11-27-plusieurs-numeros). Back-office : les voir, c'est voir
 * l'utilisateur ; les retirer, c'est le modifier. API : un compte gère les siens ; celui d'un autre répond 404, pas 403.
 */
class UserPhonePolicy
{
    use HasBeforeAbility;

    public function viewAny(User $user): bool
    {
        if (Filament::isServing()) {
            return $user->hasPermissionTo(PermissionEnum::VIEW_USERS);
        }

        return true;
    }

    public function view(User $user, UserPhone $phone): bool|Response
    {
        if (Filament::isServing()) {
            return $user->hasPermissionTo(PermissionEnum::VIEW_USERS);
        }

        return $this->owns($user, $phone);
    }

    /** Un numéro s'ajoute par un code SMS, depuis l'app seulement. */
    public function create(User $user): bool
    {
        return ! Filament::isServing();
    }

    public function update(User $user, UserPhone $phone): bool|Response
    {
        if (Filament::isServing()) {
            return false;
        }

        return $this->owns($user, $phone);
    }

    public function delete(User $user, UserPhone $phone): bool|Response
    {
        if (Filament::isServing()) {
            return $user->hasPermissionTo(PermissionEnum::UPDATE_USERS);
        }

        return $this->owns($user, $phone);
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }

    private function owns(User $user, UserPhone $phone): Response
    {
        return (int) $phone->user_id === (int) $user->getKey() ? Response::allow() : Response::denyAsNotFound();
    }
}
