<?php

namespace Modules\Auth\Support;

use Modules\Auth\Enums\VerificationChannel;
use Modules\Auth\Models\User;

/**
 * Identifiant saisi pour se connecter ou retrouver son compte : un e-mail, ou un
 * numéro avec son pays (champs `email`, `phone` et `phone_country` des formulaires
 * de connexion et de mot de passe oublié). Seul domicile de cette reconnaissance.
 */
final class Identifiers
{
    /**
     * Le canal visé, la destination normalisée (e-mail tel quel, téléphone en E.164,
     * ou la saisie brute s'il n'en est pas un), le champ saisi et le compte
     * correspondant, s'il existe. Un téléphone n'identifie un compte que s'il a été
     * vérifié.
     *
     * @param  array{email?: string|null, phone?: string|null, phone_country?: string|null}  $data  champs validés du formulaire
     * @return array{channel: VerificationChannel, destination: string, field: string, user: User|null}
     */
    public static function resolve(array $data): array
    {
        $email = trim((string) ($data['email'] ?? ''));

        if ($email !== '') {
            return [
                'channel' => VerificationChannel::EMAIL,
                'destination' => $email,
                'field' => 'email',
                'user' => User::query()->where('email', $email)->first(),
            ];
        }

        $number = trim((string) ($data['phone'] ?? ''));
        $phone = self::toE164($number, (string) ($data['phone_country'] ?? ''));

        return [
            'channel' => VerificationChannel::SMS,
            'destination' => $phone ?? $number,
            'field' => 'phone',
            'user' => $phone === null ? null : User::query()->where('phone', $phone)->whereNotNull('phone_verified_at')->first(),
        ];
    }

    /**
     * Numéro national et pays → E.164, ou `null` s'il n'est pas valide.
     */
    private static function toE164(string $number, string $country): ?string
    {
        if ($number === '' || $country === '') {
            return null;
        }

        try {
            return PhoneNumbers::toE164($number, strtoupper($country));
        } catch (\Throwable) {
            return null;
        }
    }
}
