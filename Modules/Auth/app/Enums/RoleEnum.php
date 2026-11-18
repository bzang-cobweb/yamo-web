<?php

namespace Modules\Auth\Enums;

enum RoleEnum: string
{
    /** Tous les droits : court-circuite les policies (HasBeforeAbility). */
    case SUPER_ADMIN = 'super_admin';

    /** Gestion courante ; ses permissions de base sont données par le seeder. */
    case ADMIN = 'admin';

    /**
     * Permissions que le seeder ajoute au rôle, sans retirer celles données dans
     * le back-office (décision 2026-11-18-contrat-liste-infinie-role-admin).
     *
     * @return list<PermissionEnum>
     */
    public function basePermissions(): array
    {
        return match ($this) {
            self::SUPER_ADMIN => [],
            self::ADMIN => [
                PermissionEnum::VIEW_ADDRESSES,
                PermissionEnum::CREATE_ADDRESSES,
                PermissionEnum::UPDATE_ADDRESSES,
                PermissionEnum::DELETE_ADDRESSES,
            ],
        };
    }
}
