<?php

namespace App\Policies;

use App\Models\Setting;
use Modules\Auth\Enums\PermissionEnum;
use Modules\Auth\Models\User;
use Modules\Auth\Policies\Concerns\HasBeforeAbility;

/**
 * Réglages de l'application : modifiables dans le back-office avec la permission
 * dédiée. L'API les lit sans compte (`GET /api/v1/app`), hors policy.
 */
class SettingPolicy
{
    use HasBeforeAbility;

    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo(PermissionEnum::UPDATE_SETTINGS);
    }

    public function view(User $user, Setting $setting): bool
    {
        return $user->hasPermissionTo(PermissionEnum::UPDATE_SETTINGS);
    }

    public function update(User $user, Setting $setting): bool
    {
        return $user->hasPermissionTo(PermissionEnum::UPDATE_SETTINGS);
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function delete(User $user, Setting $setting): bool
    {
        return false;
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }
}
