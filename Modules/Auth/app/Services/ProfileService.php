<?php

namespace Modules\Auth\Services;

use Modules\Auth\Models\User;

/**
 * Profil de l'utilisateur connecté.
 */
final class ProfileService
{
    /**
     * Modifie son propre compte avec des données déjà validées (aujourd'hui : la
     * langue, par le formulaire `auth.profile.locale`).
     *
     * @param  array{locale?: string}  $data
     */
    public function update(User $user, array $data): User
    {
        $user->update($data);

        return $user;
    }
}
