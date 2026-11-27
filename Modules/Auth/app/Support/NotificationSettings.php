<?php

namespace Modules\Auth\Support;

/**
 * Les choix de notifications d'un compte (décision 2026-11-27-notifications-du-profil), un seul domicile pour leurs
 * clés et leurs défauts :
 * - `account_activity` et `reminders` : les canaux choisis parmi `CHANNELS` (tous par défaut) ;
 * - `offers` et `news` : oui ou non (oui par défaut).
 * Enregistrés dans `users.notification_settings` ; une clé absente (ou la colonne vide) vaut son défaut.
 */
final class NotificationSettings
{
    public const EMAIL = 'email';

    public const SMS = 'sms';

    /** À venir : le choix est gardé, rien n'est encore envoyé par push. */
    public const PUSH = 'push';

    /** @var list<string> */
    public const CHANNELS = [self::EMAIL, self::SMS, self::PUSH];

    /** Les choix de canaux. */
    public const ACCOUNT_ACTIVITY = 'account_activity';

    public const REMINDERS = 'reminders';

    /** @var list<string> */
    public const CHANNEL_GROUPS = [self::ACCOUNT_ACTIVITY, self::REMINDERS];

    /** Les choix oui ou non. */
    public const OFFERS = 'offers';

    public const NEWS = 'news';

    /** @var list<string> */
    public const TOGGLES = [self::OFFERS, self::NEWS];

    /**
     * @return array{account_activity: list<string>, reminders: list<string>, offers: bool, news: bool}
     */
    public static function defaults(): array
    {
        return [
            self::ACCOUNT_ACTIVITY => self::CHANNELS,
            self::REMINDERS => self::CHANNELS,
            self::OFFERS => true,
            self::NEWS => true,
        ];
    }

    /**
     * Les choix enregistrés, complétés par les défauts ; une valeur inconnue est ignorée.
     *
     * @param  array<string, mixed>|null  $stored
     * @return array{account_activity: list<string>, reminders: list<string>, offers: bool, news: bool}
     */
    public static function resolve(?array $stored): array
    {
        $settings = self::defaults();

        foreach (self::CHANNEL_GROUPS as $group) {
            if (isset($stored[$group]) && is_array($stored[$group])) {
                $settings[$group] = array_values(array_intersect(self::CHANNELS, $stored[$group]));
            }
        }

        foreach (self::TOGGLES as $toggle) {
            if (isset($stored[$toggle])) {
                $settings[$toggle] = (bool) $stored[$toggle];
            }
        }

        return $settings;
    }

    /**
     * Les choix saisis, posés sur ceux déjà enregistrés : une clé non envoyée ne change pas.
     *
     * @param  array<string, mixed>|null  $stored
     * @param  array<string, mixed>  $input  données validées
     * @return array{account_activity: list<string>, reminders: list<string>, offers: bool, news: bool}
     */
    public static function merge(?array $stored, array $input): array
    {
        $settings = self::resolve($stored);

        foreach (self::CHANNEL_GROUPS as $group) {
            if (array_key_exists($group, $input)) {
                $settings[$group] = array_values(array_intersect(self::CHANNELS, (array) ($input[$group] ?? [])));
            }
        }

        foreach (self::TOGGLES as $toggle) {
            if (array_key_exists($toggle, $input) && $input[$toggle] !== null) {
                $settings[$toggle] = (bool) $input[$toggle];
            }
        }

        return $settings;
    }
}
