<?php

namespace Modules\Auth\Enums;

enum RoleEnum: string
{
    /** Tous les droits : court-circuite les policies (HasBeforeAbility). */
    case SUPER_ADMIN = 'super_admin';

    /**
     * Compte inscrit depuis l'app (décision 2026-11-18-role-membre). Ses droits
     * sur les offres viennent du profil de domaine.
     */
    case MEMBER = 'member';

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
            self::SUPER_ADMIN, self::MEMBER => [],
            // Les adresses d'un utilisateur sont sur sa page d'édition (décision 2026-11-18-role-admin-acces-back-office).
            self::ADMIN => [
                PermissionEnum::ACCESS_ADMIN,
                PermissionEnum::VIEW_USERS,
                PermissionEnum::UPDATE_USERS,
                PermissionEnum::VIEW_ADDRESSES,
                PermissionEnum::CREATE_ADDRESSES,
                PermissionEnum::UPDATE_ADDRESSES,
                PermissionEnum::DELETE_ADDRESSES,
                // Modération des avis (décision 2026-11-19-contrat-avis-offres).
                PermissionEnum::VIEW_OFFER_REVIEWS,
                PermissionEnum::DELETE_OFFER_REVIEWS,
                // Offres et catégories (décision 2026-11-18-offres-et-domaines).
                PermissionEnum::VIEW_OFFERS,
                PermissionEnum::CREATE_OFFERS,
                PermissionEnum::UPDATE_OFFERS,
                PermissionEnum::DELETE_OFFERS,
                // Les partenaires de l'accueil (décision 2026-11-23-module-partenaires).
                PermissionEnum::VIEW_PARTNERS,
                PermissionEnum::CREATE_PARTNERS,
                PermissionEnum::UPDATE_PARTNERS,
                PermissionEnum::DELETE_PARTNERS,
            ],
        };
    }
}
