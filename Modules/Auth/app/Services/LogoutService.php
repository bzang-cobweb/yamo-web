<?php

namespace Modules\Auth\Services;

use Laravel\Sanctum\PersonalAccessToken;
use Modules\Auth\Models\User;

/**
 * Déconnexion de l'app mobile.
 */
final class LogoutService
{
    /**
     * Révoque le jeton qui a authentifié la requête, et lui seul : les autres
     * appareils restent connectés.
     */
    public function logout(User $user): void
    {
        $token = $user->currentAccessToken();

        if ($token instanceof PersonalAccessToken) {
            $token->delete();
        }
    }
}
