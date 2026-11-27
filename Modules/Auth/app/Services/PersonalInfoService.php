<?php

namespace Modules\Auth\Services;

use Modules\Auth\Models\User;

/**
 * Informations personnelles de l'utilisateur connecté.
 */
final class PersonalInfoService
{
    /**
     * @param  array{first_name: string, last_name: string, birth_date?: string|null, country_id?: string|null, region_id?: string|null, description?: string|null}  $data  données validées
     */
    public function update(User $user, array $data): User
    {
        $user->update([
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            // Champ vide (« Effacer » dans l'app) : la date est retirée.
            'birth_date' => $data['birth_date'] ?? null,
            'country_id' => $data['country_id'] ?? null,
            // Région validée contre le pays choisi (liste dépendante) ; sans pays, aucune.
            'region_id' => isset($data['country_id']) ? ($data['region_id'] ?? null) : null,
            'description' => $data['description'] ?? null,
        ]);

        return $user;
    }
}
