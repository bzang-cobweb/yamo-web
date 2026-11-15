<?php

namespace Modules\Auth\Policies;

use Filament\Facades\Filament;
use Modules\Auth\Enums\PermissionEnum;
use Modules\Auth\Models\User;
use Modules\Auth\Policies\Concerns\HasBeforeAbility;

class UserPolicy
{
    use HasBeforeAbility {
        before as superAdminBefore;
    }

    public function before(User $user, string $ability, mixed ...$arguments): ?bool
    {
        // Personne, pas même le super administrateur, ne supprime son propre compte.
        if ($ability === 'delete' && ($arguments[0] ?? null) instanceof User && $arguments[0]->is($user)) {
            return false;
        }

        return $this->superAdminBefore($user, $ability);
    }

    public function viewAny(User $user): bool
    {
        if (Filament::isServing()) {
            return $user->hasPermissionTo(PermissionEnum::VIEW_USERS);
        }

        // Site et API : pas de liste des comptes.
        return false;
    }

    public function view(User $user, User $model): bool
    {
        if (Filament::isServing()) {
            return $user->hasPermissionTo(PermissionEnum::VIEW_USERS);
        }

        // Site et API : chacun voit son propre compte.
        return $model->is($user);
    }

    /**
     * Consulter son propre compte (`GET /api/v1/auth/me`) : même règle que
     * voir ce compte-là.
     */
    public function me(User $user): bool
    {
        return $this->view($user, $user);
    }

    public function create(User $user): bool
    {
        if (Filament::isServing()) {
            return $user->hasPermissionTo(PermissionEnum::CREATE_USERS);
        }

        // Site et API : un compte connecté ne crée pas d'autre compte.
        return false;
    }

    public function update(User $user, User $model): bool
    {
        if (Filament::isServing()) {
            return $user->hasPermissionTo(PermissionEnum::UPDATE_USERS);
        }

        // Site et API : chacun modifie son propre compte.
        return $model->is($user);
    }

    public function delete(User $user, User $model): bool
    {
        if (Filament::isServing()) {
            // On ne supprime pas son propre compte depuis le back-office.
            return $user->hasPermissionTo(PermissionEnum::DELETE_USERS) && ! $model->is($user);
        }

        // Site et API : pas de suppression de compte pour l'instant.
        return false;
    }

    public function deleteAny(User $user): bool
    {
        if (Filament::isServing()) {
            return $user->hasPermissionTo(PermissionEnum::DELETE_USERS);
        }

        // Site et API : pas de suppression groupée.
        return false;
    }
}
