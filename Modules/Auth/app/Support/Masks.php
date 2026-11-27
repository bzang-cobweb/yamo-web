<?php

namespace Modules\Auth\Support;

use Modules\Auth\Enums\VerificationChannel;

/**
 * Seul domicile du masquage d'un e-mail ou d'un numéro de téléphone affiché (décision
 * 2026-11-27-email-telephone-masques) : la destination d'un code, le profil, les formulaires, les panneaux servis.
 * Le back-office montre les valeurs entières.
 */
final class Masks
{
    /**
     * « jean.dupont@example.com » → « j•••@example.com » ; `null` reste `null`.
     */
    public static function email(?string $email): ?string
    {
        if ($email === null || $email === '') {
            return $email;
        }

        if (! str_contains($email, '@')) {
            return mb_substr($email, 0, 1).'•••';
        }

        [$name, $domain] = explode('@', $email, 2);

        return mb_substr($name, 0, 1).'•••@'.$domain;
    }

    /**
     * Un numéro E.164 (ou lisible) : ses trois premiers et ses deux derniers caractères, « +41 ••• 67 » ; `null` reste `null`.
     */
    public static function phone(?string $phone): ?string
    {
        if ($phone === null || $phone === '') {
            return $phone;
        }

        $compact = preg_replace('/\s+/', '', $phone) ?? $phone;

        return mb_substr($compact, 0, 3).' ••• '.mb_substr($compact, -2);
    }

    /**
     * La destination d'un code, selon son canal.
     */
    public static function destination(VerificationChannel $channel, string $destination): string
    {
        return (string) ($channel === VerificationChannel::EMAIL ? self::email($destination) : self::phone($destination));
    }
}
