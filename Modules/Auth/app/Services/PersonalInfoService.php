<?php

namespace Modules\Auth\Services;

use Modules\Auth\Models\User;

/**
 * Informations personnelles de l'utilisateur connecté.
 */
final class PersonalInfoService
{
    /**
     * @param  array{first_name: string, last_name: string, gender: string, birth_date?: string|null}  $data  données validées
     */
    public function update(User $user, array $data): User
    {
        $user->update([
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'gender' => $data['gender'],
            // Champ vide (« Effacer » dans l'app) : la date est retirée.
            'birth_date' => $data['birth_date'] ?? null,
        ]);

        return $user;
    }
}
