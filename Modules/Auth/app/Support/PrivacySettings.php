<?php

namespace Modules\Auth\Support;

/**
 * Les réglages de confidentialité d'un compte (décision 2026-11-27-confidentialite), un seul domicile pour leurs clés
 * et leurs défauts :
 * - `profile_visibility` : qui voit sa page publique, parmi `VISIBILITIES` (tout le monde par défaut) ;
 * - `review_location` : montrer son pays, sa région et sa ville sous ses avis (non par défaut).
 * Enregistrés dans `users.privacy_settings` ; une clé absente (ou la colonne vide) vaut son défaut.
 */
final class PrivacySettings
{
    public const VISIBILITY = 'profile_visibility';

    /** Tout membre connecté. */
    public const ANYONE = 'anyone';

    /** Les membres qui ont au moins une offre. */
    public const OWNERS = 'owners';

    /** Les membres avec qui il a une demande en commun (l'un a demandé une offre de l'autre). */
    public const CONNECTIONS = 'connections';

    public const OWNERS_AND_CONNECTIONS = 'owners_and_connections';

    /** @var list<string> */
    public const VISIBILITIES = [self::ANYONE, self::OWNERS, self::CONNECTIONS, self::OWNERS_AND_CONNECTIONS];

    public const REVIEW_LOCATION = 'review_location';

    /**
     * @return array{profile_visibility: string, review_location: bool}
     */
    public static function defaults(): array
    {
        return [
            self::VISIBILITY => self::ANYONE,
            self::REVIEW_LOCATION => false,
        ];
    }

    /**
     * Les réglages enregistrés, complétés par les défauts ; une valeur inconnue est ignorée.
     *
     * @param  array<string, mixed>|null  $stored
     * @return array{profile_visibility: string, review_location: bool}
     */
    public static function resolve(?array $stored): array
    {
        $settings = self::defaults();

        if (isset($stored[self::VISIBILITY]) && in_array($stored[self::VISIBILITY], self::VISIBILITIES, true)) {
            $settings[self::VISIBILITY] = $stored[self::VISIBILITY];
        }

        if (isset($stored[self::REVIEW_LOCATION])) {
            $settings[self::REVIEW_LOCATION] = (bool) $stored[self::REVIEW_LOCATION];
        }

        return $settings;
    }

    /**
     * Les réglages saisis, posés sur ceux déjà enregistrés : une clé non envoyée ne change pas.
     *
     * @param  array<string, mixed>|null  $stored
     * @param  array<string, mixed>  $input  données validées
     * @return array{profile_visibility: string, review_location: bool}
     */
    public static function merge(?array $stored, array $input): array
    {
        return self::resolve([...self::resolve($stored), ...array_filter($input, fn (mixed $value): bool => $value !== null)]);
    }
}
