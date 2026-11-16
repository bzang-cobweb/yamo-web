<?php

namespace Modules\Location\Policies;

use Filament\Facades\Filament;
use Modules\Auth\Enums\PermissionEnum;
use Modules\Auth\Models\User;
use Modules\Auth\Policies\Concerns\HasBeforeAbility;
use Modules\Location\Models\Region;

class RegionPolicy
{
    use HasBeforeAbility;

    public function viewAny(User $user): bool
    {
        if (Filament::isServing()) {
            return $user->hasPermissionTo(PermissionEnum::VIEW_REGIONS);
        }

        // Site et API : donnée de référence, lisible par tout compte connecté.
        return true;
    }

    public function view(User $user, Region $region): bool
    {
        if (Filament::isServing()) {
            return $user->hasPermissionTo(PermissionEnum::VIEW_REGIONS);
        }

        // Site et API : donnée de référence, lisible par tout compte connecté.
        return true;
    }

    public function create(User $user): bool
    {
        if (Filament::isServing()) {
            return $user->hasPermissionTo(PermissionEnum::CREATE_REGIONS);
        }

        // Site et API : les régions ne sont gérées que dans le back-office.
        return false;
    }

    public function update(User $user, Region $region): bool
    {
        if (Filament::isServing()) {
            return $user->hasPermissionTo(PermissionEnum::UPDATE_REGIONS);
        }

        // Site et API : les régions ne sont gérées que dans le back-office.
        return false;
    }

    public function delete(User $user, Region $region): bool
    {
        if (Filament::isServing()) {
            return $user->hasPermissionTo(PermissionEnum::DELETE_REGIONS);
        }

        // Site et API : les régions ne sont gérées que dans le back-office.
        return false;
    }

    public function deleteAny(User $user): bool
    {
        if (Filament::isServing()) {
            return $user->hasPermissionTo(PermissionEnum::DELETE_REGIONS);
        }

        // Site et API : les régions ne sont gérées que dans le back-office.
        return false;
    }
}
