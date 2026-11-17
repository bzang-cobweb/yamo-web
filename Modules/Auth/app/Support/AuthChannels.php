<?php

namespace Modules\Auth\Support;

use LogicException;

/**
 * Canaux de compte de l'application : l'e-mail, le téléphone ou les deux
 * (`config('auth.channels')`, `AUTH_CHANNEL_EMAIL` et `AUTH_CHANNEL_PHONE`).
 * Un canal désactivé ne peut plus être inscrit, ajouté ni changé ; un compte
 * existant se connecte toujours avec son identifiant
 * (décision 2026-11-20-canaux-email-telephone).
 */
final class AuthChannels
{
    public static function email(): bool
    {
        return self::enabled()['email'];
    }

    public static function phone(): bool
    {
        return self::enabled()['phone'];
    }

    public static function both(): bool
    {
        return self::email() && self::phone();
    }

    /**
     * @return array{email: bool, phone: bool}
     *
     * @throws LogicException aucun canal activé : l'authentification est impossible
     */
    public static function enabled(): array
    {
        $channels = [
            'email' => (bool) config('auth.channels.email', true),
            'phone' => (bool) config('auth.channels.phone', true),
        ];

        if (! $channels['email'] && ! $channels['phone']) {
            throw new LogicException('AUTH_CHANNEL_EMAIL et AUTH_CHANNEL_PHONE sont désactivés : activez au moins un canal de compte.');
        }

        return $channels;
    }

    /**
     * Libellé de l'identifiant de connexion : « E-mail ou téléphone », « E-mail »
     * ou « Téléphone ».
     */
    public static function identifierLabel(): string
    {
        return match (true) {
            self::both() => __('auth::user.identifier'),
            self::email() => __('auth::user.email'),
            default => __('auth::user.phone'),
        };
    }

    /**
     * Aide de l'identifiant : le format international d'un téléphone, s'il est accepté.
     */
    public static function identifierHelp(): ?string
    {
        return self::phone() ? __('auth::form.login.identifier_help') : null;
    }
}
