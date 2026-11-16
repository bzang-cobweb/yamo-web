<?php

namespace Modules\Auth\Services;

use Modules\Auth\Models\User;

/**
 * Préférences de l'utilisateur connecté (aujourd'hui : la langue).
 */
final class PreferencesService
{
    /**
     * @param  array{locale: string}  $data  données validées
     */
    public function update(User $user, array $data): User
    {
        $user->update(['locale' => $data['locale']]);

        return $user;
    }
}
