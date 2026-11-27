<?php

namespace Modules\Auth\Services;

use Modules\Auth\Models\User;
use Modules\Auth\Support\PrivacySettings;

/**
 * Les réglages de confidentialité de l'utilisateur connecté (décision 2026-11-27-confidentialite) : « Visibilité du
 * profil » et « Avis » les enregistrent tous deux, chacun ses clés.
 */
final class PrivacySettingsService
{
    /**
     * @param  array<string, mixed>  $data  données validées
     */
    public function update(User $user, array $data): User
    {
        $user->forceFill(['privacy_settings' => PrivacySettings::merge($user->privacy_settings, $data)])->save();

        return $user;
    }
}
