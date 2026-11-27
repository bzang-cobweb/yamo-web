<?php

namespace Modules\Auth\Services;

use Modules\Auth\Models\EmergencyContact;
use Modules\Auth\Models\User;
use Modules\Auth\Support\PhoneNumbers;

/**
 * Le contact d'urgence de l'utilisateur connecté (décision 2026-11-27-contact-d-urgence) : créé ou modifié, un seul
 * par compte ; le téléphone en E.164, comme celui du compte.
 */
final class EmergencyContactService
{
    /**
     * @param  array<string, mixed>  $data  données validées
     */
    public function save(User $user, array $data): EmergencyContact
    {
        $country = PhoneNumbers::country((string) $data['phone_country']);

        return $user->emergencyContact()->updateOrCreate([], [
            'name' => trim((string) $data['name']),
            'relationship' => self::nullIfBlank($data['relationship'] ?? null),
            'email' => self::nullIfBlank($data['email'] ?? null),
            'phone' => PhoneNumbers::toE164((string) $data['phone'], (string) $country?->code),
            'phone_country_id' => $country?->getKey(),
            'locale' => $data['locale'],
        ]);
    }

    private static function nullIfBlank(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}
