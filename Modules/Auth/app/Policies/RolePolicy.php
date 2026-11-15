<?php

namespace Modules\Auth\Policies;

use Filament\Facades\Filament;
use Modules\Auth\Enums\PermissionEnum;
use Modules\Auth\Models\Role;
use Modules\Auth\Models\User;
use Modules\Auth\Policies\Concerns\HasBeforeAbility;

class RolePolicy
{
    use HasBeforeAbility;

    public function viewAny(User $user): bool
    {
        if (Filament::isServing()) {
            return $user->hasPermissionTo(PermissionEnum::VIEW_ROLES);
        }

        // Site et API : les rôles ne sont gérés que dans le back-office.
        return false;
    }

    public function view(User $user, Role $role): bool
    {
        if (Filament::isServing()) {
            return $user->hasPermissionTo(PermissionEnum::VIEW_ROLES);
        }

        // Site et API : les rôles ne sont gérés que dans le back-office.
        return false;
    }

    public function create(User $user): bool
    {
        if (Filament::isServing()) {
            return $user->hasPermissionTo(PermissionEnum::CREATE_ROLES);
        }

        // Site et API : les rôles ne sont gérés que dans le back-office.
        return false;
    }

    public function update(User $user, Role $role): bool
    {
        if (Filament::isServing()) {
            return $user->hasPermissionTo(PermissionEnum::UPDATE_ROLES);
        }

        // Site et API : les rôles ne sont gérés que dans le back-office.
        return false;
    }

    public function delete(User $user, Role $role): bool
    {
        if (Filament::isServing()) {
            return $user->hasPermissionTo(PermissionEnum::DELETE_ROLES);
        }

        // Site et API : les rôles ne sont gérés que dans le back-office.
        return false;
    }

    public function deleteAny(User $user): bool
    {
        if (Filament::isServing()) {
            return $user->hasPermissionTo(PermissionEnum::DELETE_ROLES);
        }

        // Site et API : les rôles ne sont gérés que dans le back-office.
        return false;
    }
}
