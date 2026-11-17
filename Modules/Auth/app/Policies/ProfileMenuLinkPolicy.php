<?php

namespace Modules\Auth\Policies;

use Modules\Auth\Enums\PermissionEnum;
use Modules\Auth\Models\ProfileMenuLink;
use Modules\Auth\Models\User;
use Modules\Auth\Policies\Concerns\HasBeforeAbility;

/**
 * Menu du profil : un réglage de l'application, géré dans le back-office avec la
 * permission des réglages. Ses liens ne se créent ni ne se suppriment : chacun
 * correspond à un écran de l'app. L'API le lit pour le compte connecté
 * (`GET /api/v1/auth/me/menu`), hors policy.
 */
class ProfileMenuLinkPolicy
{
    use HasBeforeAbility {
        before as superAdminBefore;
    }

    /**
     * Ni création ni suppression, pas même pour le super administrateur : un lien
     * sans écran ne mènerait nulle part.
     */
    public function before(User $user, string $ability): ?bool
    {
        if (in_array($ability, ['create', 'delete', 'deleteAny'], true)) {
            return false;
        }

        return $this->superAdminBefore($user, $ability);
    }

    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo(PermissionEnum::UPDATE_SETTINGS);
    }

    public function view(User $user, ProfileMenuLink $link): bool
    {
        return $user->hasPermissionTo(PermissionEnum::UPDATE_SETTINGS);
    }

    public function update(User $user, ProfileMenuLink $link): bool
    {
        return $user->hasPermissionTo(PermissionEnum::UPDATE_SETTINGS);
    }

    public function reorder(User $user): bool
    {
        return $user->hasPermissionTo(PermissionEnum::UPDATE_SETTINGS);
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function delete(User $user, ProfileMenuLink $link): bool
    {
        return false;
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }
}
