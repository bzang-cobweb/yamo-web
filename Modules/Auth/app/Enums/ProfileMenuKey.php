<?php

namespace Modules\Auth\Enums;

use Modules\Auth\Models\User;
use Modules\Auth\Support\AuthChannels;

/**
 * Les liens possibles du menu du profil : chacun ouvre l'écran de l'app désigné
 * par `path`, que l'app suit sans le connaître d'avance ; ajouter un lien vers un
 * écran existant ne demande donc pas de nouvelle version de l'app. L'ordre du
 * menu est fixé par `ProfileMenuService`
 * (décision 2026-11-17-regions-alphabetiques-menu-en-code).
 */
enum ProfileMenuKey: string
{
    case PERSONAL = 'personal';
    case EMAIL = 'email';
    case PHONE = 'phone';
    case PASSWORD = 'password';
    case PREFERENCES = 'preferences';

    /**
     * Route de l'écran dans l'app (Expo Router).
     */
    public function path(): string
    {
        return '/profile/'.$this->value;
    }

    /**
     * Le lien d'un canal désactivé (`AuthChannels`) n'est jamais proposé.
     */
    public function isAvailable(): bool
    {
        return match ($this) {
            self::EMAIL => AuthChannels::email(),
            self::PHONE => AuthChannels::phone(),
            default => true,
        };
    }

    /**
     * Libellé montré à l'utilisateur : ajouter ou changer, selon son compte.
     */
    public function labelFor(User $user): string
    {
        return __('auth::mobile.profile.'.match ($this) {
            self::PERSONAL => 'personalInfo',
            self::EMAIL => filled($user->email) ? 'changeEmail' : 'addEmail',
            self::PHONE => filled($user->phone) ? 'changePhone' : 'addPhone',
            self::PASSWORD => 'password',
            self::PREFERENCES => 'preferences',
        });
    }
}
