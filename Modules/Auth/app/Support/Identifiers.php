<?php

namespace Modules\Auth\Support;

use Modules\Auth\Enums\VerificationChannel;
use Modules\Auth\Models\User;

/**
 * Identifiant saisi pour se connecter ou retrouver son compte : un e-mail, un
 * téléphone au format international (`+41 79…`, `0041…`), ou un numéro national
 * avec son pays (champ téléphone, quand l'application n'a que ce canal). Seul
 * domicile de cette reconnaissance (connexion, mot de passe oublié).
 */
final class Identifiers
{
    /**
     * Le canal visé, la destination normalisée (e-mail tel quel, téléphone en E.164,
     * ou la saisie brute si elle n'en est pas un) et le compte correspondant, s'il
     * existe. Un téléphone n'identifie un compte que s'il a été vérifié.
     *
     * @param  string|null  $country  pays du numéro (code ISO), s'il a été saisi à part
     * @return array{channel: VerificationChannel, destination: string, user: User|null}
     */
    public static function resolve(string $identifier, ?string $country = null): array
    {
        $identifier = trim($identifier);

        if ($country !== null && $country !== '') {
            $identifier = self::toInternational($identifier, $country);
        }

        if (str_contains($identifier, '@')) {
            return [
                'channel' => VerificationChannel::EMAIL,
                'destination' => $identifier,
                'user' => User::query()->where('email', $identifier)->first(),
            ];
        }

        $phone = PhoneNumbers::fromInternational($identifier);

        return [
            'channel' => VerificationChannel::SMS,
            'destination' => $phone ?? $identifier,
            'user' => $phone === null ? null : User::query()->where('phone', $phone)->whereNotNull('phone_verified_at')->first(),
        ];
    }

    /**
     * Numéro national et pays → E.164 ; saisie inchangée si le numéro n'est pas valide.
     */
    private static function toInternational(string $number, string $country): string
    {
        try {
            return PhoneNumbers::toE164($number, strtoupper($country));
        } catch (\Throwable) {
            return $number;
        }
    }
}
