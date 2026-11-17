<?php

namespace Modules\Auth\Enums;

use Modules\Auth\Forms\EmailChangeForm;
use Modules\Auth\Forms\PasswordForm;
use Modules\Auth\Forms\PersonalInfoForm;
use Modules\Auth\Forms\PhoneChangeForm;
use Modules\Auth\Forms\PreferencesForm;
use Modules\Auth\Models\User;
use Modules\Auth\Support\AuthChannels;
use Modules\Form\Support\Form;

/**
 * Les liens possibles du menu du profil : chacun ouvre un formulaire, que l'app
 * affiche et envoie selon sa définition (`Form::submission()`), sans le connaître
 * d'avance. Ajouter un lien = un cas ici, son formulaire et sa route d'API, sans
 * nouvelle version de l'app (décision 2026-11-17-formulaires-profil-generiques).
 * L'ordre du menu est fixé par `ProfileMenuService`.
 */
enum ProfileMenuKey: string
{
    case PERSONAL = 'personal';
    case EMAIL = 'email';
    case PHONE = 'phone';
    case PASSWORD = 'password';
    case PREFERENCES = 'preferences';

    /**
     * Formulaire ouvert par le lien.
     *
     * @return class-string<Form>
     */
    public function formClass(): string
    {
        return match ($this) {
            self::PERSONAL => PersonalInfoForm::class,
            self::EMAIL => EmailChangeForm::class,
            self::PHONE => PhoneChangeForm::class,
            self::PASSWORD => PasswordForm::class,
            self::PREFERENCES => PreferencesForm::class,
        };
    }

    /**
     * Nom du formulaire dans l'API (`auth.profile.personal`), que l'app ouvre.
     */
    public function form(): string
    {
        return $this->formClass()::name();
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
