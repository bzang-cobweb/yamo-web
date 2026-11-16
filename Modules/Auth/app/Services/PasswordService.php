<?php

namespace Modules\Auth\Services;

use Laravel\Sanctum\PersonalAccessToken;
use Modules\Auth\Models\User;

/**
 * Mot de passe de l'utilisateur connecté.
 */
final class PasswordService
{
    /**
     * Enregistre le nouveau mot de passe (haché par le cast du modèle) et révoque
     * les jetons des autres appareils : seul l'appareil qui a fait la demande reste
     * connecté (décision 2026-11-16-contrat-menu-et-profil).
     */
    public function update(User $user, string $password): void
    {
        $user->update(['password' => $password]);

        $current = $user->currentAccessToken();

        $user->tokens()
            ->when($current instanceof PersonalAccessToken, fn ($query) => $query->whereKeyNot($current->getKey()))
            ->delete();
    }
}
