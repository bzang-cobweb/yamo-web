<?php

namespace Modules\Auth\Enums;

use Modules\Auth\Models\User;
use Modules\Auth\Support\AuthChannels;

/**
 * Les liens possibles du menu du profil : chacun ouvre un écran de l'app, qui le
 * reconnaît à sa valeur (décision 2026-11-17-tri-regions-menu-profil). Un nouvel
 * écran s'ajoute ici et par une migration qui crée sa ligne.
 */
enum ProfileMenuKey: string
{
    case PERSONAL = 'personal';
    case EMAIL = 'email';
    case PHONE = 'phone';
    case PASSWORD = 'password';
    case PREFERENCES = 'preferences';

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

    /**
     * Nom de l'écran dans le back-office.
     */
    public function label(): string
    {
        return __('auth::profile_menu.keys.'.$this->value);
    }
}
