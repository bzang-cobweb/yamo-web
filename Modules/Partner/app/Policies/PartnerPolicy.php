<?php

namespace Modules\Partner\Policies;

use Filament\Facades\Filament;
use Modules\Auth\Enums\PermissionEnum;
use Modules\Auth\Models\User;
use Modules\Auth\Policies\Concerns\HasBeforeAbility;
use Modules\Partner\Models\Partner;

/**
 * Les partenaires se gèrent au back-office seulement (décision 2026-11-23-module-partenaires) ; l'app ne fait
 * que lire les visibles, sans compte (`PartnerService`).
 */
class PartnerPolicy
{
    use HasBeforeAbility;

    public function viewAny(User $user): bool
    {
        return Filament::isServing() && $user->hasPermissionTo(PermissionEnum::VIEW_PARTNERS);
    }

    public function view(User $user, Partner $partner): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return Filament::isServing() && $user->hasPermissionTo(PermissionEnum::CREATE_PARTNERS);
    }

    public function update(User $user, Partner $partner): bool
    {
        return Filament::isServing() && $user->hasPermissionTo(PermissionEnum::UPDATE_PARTNERS);
    }

    public function delete(User $user, Partner $partner): bool
    {
        return Filament::isServing() && $user->hasPermissionTo(PermissionEnum::DELETE_PARTNERS);
    }

    public function deleteAny(User $user): bool
    {
        return Filament::isServing() && $user->hasPermissionTo(PermissionEnum::DELETE_PARTNERS);
    }
}
