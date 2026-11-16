<?php

namespace Modules\Location\Policies;

use Filament\Facades\Filament;
use Modules\Auth\Enums\PermissionEnum;
use Modules\Auth\Models\User;
use Modules\Auth\Policies\Concerns\HasBeforeAbility;
use Modules\Location\Models\Country;

class CountryPolicy
{
    use HasBeforeAbility;

    public function viewAny(User $user): bool
    {
        if (Filament::isServing()) {
            return $user->hasPermissionTo(PermissionEnum::VIEW_COUNTRIES);
        }

        // Site et API : donnée de référence, lisible par tout compte connecté.
        return true;
    }

    public function view(User $user, Country $country): bool
    {
        if (Filament::isServing()) {
            return $user->hasPermissionTo(PermissionEnum::VIEW_COUNTRIES);
        }

        // Site et API : donnée de référence, lisible par tout compte connecté.
        return true;
    }

    public function create(User $user): bool
    {
        if (Filament::isServing()) {
            return $user->hasPermissionTo(PermissionEnum::CREATE_COUNTRIES);
        }

        // Site et API : les pays ne sont gérés que dans le back-office.
        return false;
    }

    public function update(User $user, Country $country): bool
    {
        if (Filament::isServing()) {
            return $user->hasPermissionTo(PermissionEnum::UPDATE_COUNTRIES);
        }

        // Site et API : les pays ne sont gérés que dans le back-office.
        return false;
    }

    public function delete(User $user, Country $country): bool
    {
        if (Filament::isServing()) {
            return $user->hasPermissionTo(PermissionEnum::DELETE_COUNTRIES);
        }

        // Site et API : les pays ne sont gérés que dans le back-office.
        return false;
    }

    public function deleteAny(User $user): bool
    {
        if (Filament::isServing()) {
            return $user->hasPermissionTo(PermissionEnum::DELETE_COUNTRIES);
        }

        // Site et API : les pays ne sont gérés que dans le back-office.
        return false;
    }
}
