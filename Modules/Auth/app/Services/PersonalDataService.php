<?php

namespace Modules\Auth\Services;

use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use Modules\Auth\Mail\PersonalDataMail;
use Modules\Auth\Models\User;
use Modules\Auth\Models\UserAddress;
use Modules\Auth\Models\UserPhone;

/**
 * « Mes données » (décision 2026-11-27-confidentialite) : les données du compte, en JSON, envoyées en pièce jointe à
 * son adresse e-mail vérifiée, tout de suite (aucune file d'attente ne tourne). Jamais à une adresse non vérifiée.
 */
final class PersonalDataService
{
    /**
     * @throws ValidationException pas d'adresse e-mail vérifiée (erreur sur `email`)
     */
    public function send(User $user): void
    {
        if ($user->email === null || $user->email_verified_at === null) {
            throw ValidationException::withMessages(['email' => __('auth::privacy.data.no_verified_email')]);
        }

        Mail::to($user->email)->locale(App::getLocale())->send(new PersonalDataMail(
            json_encode($this->export($user), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR),
        ));
    }

    /**
     * Les données du compte tenues par le module Auth : le compte, ses adresses, son contact d'urgence, ses réglages.
     * Jamais le mot de passe ni les jetons.
     *
     * @return array<string, mixed>
     */
    public function export(User $user): array
    {
        $user->loadMissing(['country', 'region', 'phoneCountry', 'identityCountry', 'addresses.address', 'emergencyContact', 'phones']);

        return [
            'exported_at' => now()->toIso8601String(),
            'account' => [
                'id' => $user->getKey(),
                'name' => $user->name,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'gender' => $user->gender?->value,
                'birth_date' => $user->birth_date?->format('Y-m-d'),
                'email' => $user->email,
                'email_verified_at' => $user->email_verified_at?->toIso8601String(),
                'phone' => $user->phone,
                'phone_verified_at' => $user->phone_verified_at?->toIso8601String(),
                'country' => $user->country?->code,
                'region' => $user->region === null ? null : (string) $user->region->name,
                'description' => $user->description,
                'locale' => $user->locale,
                'identity_country' => $user->identityCountry?->code,
                'identity_document_type' => $user->identity_document_type?->value,
                'identified_at' => $user->identified_at?->toIso8601String(),
                'created_at' => $user->created_at?->toIso8601String(),
            ],
            'addresses' => $user->addresses->map(fn (UserAddress $address): array => [
                'first_name' => $address->first_name,
                'last_name' => $address->last_name,
                'indication' => $address->indication,
                'is_residence' => (bool) $address->is_residence,
                'address' => $address->address?->only(['street', 'zip', 'city']),
            ])->all(),
            // Numéros supplémentaires (décision 2026-11-27-plusieurs-numeros).
            'phones' => $user->phones->map(fn (UserPhone $phone): array => [
                'phone' => $phone->phone,
                'verified_at' => $phone->verified_at?->toIso8601String(),
            ])->all(),
            'emergency_contact' => $user->emergencyContact?->only(['name', 'relationship', 'email', 'phone', 'locale']),
            'notification_settings' => $user->notificationSettings(),
            'privacy_settings' => $user->privacySettings(),
        ];
    }
}
