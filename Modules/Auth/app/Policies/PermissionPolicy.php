<?php

namespace Modules\Auth\Policies;

use Filament\Facades\Filament;
use Modules\Auth\Enums\PermissionEnum;
use Modules\Auth\Models\Permission;
use Modules\Auth\Models\User;
use Modules\Auth\Policies\Concerns\HasBeforeAbility;

/**
 * Les permissions sont déclarées dans PermissionEnum : en lecture seule ici,
 * sans création, modification ni suppression.
 */
class PermissionPolicy
{
    use HasBeforeAbility;

    public function viewAny(User $user): bool
    {
        if (Filament::isServing()) {
            return $user->hasPermissionTo(PermissionEnum::VIEW_PERMISSIONS);
        }

        // Site et API : les permissions ne sont consultées que dans le back-office.
        return false;
    }

    public function view(User $user, Permission $permission): bool
    {
        if (Filament::isServing()) {
            return $user->hasPermissionTo(PermissionEnum::VIEW_PERMISSIONS);
        }

        // Site et API : les permissions ne sont consultées que dans le back-office.
        return false;
    }
}
