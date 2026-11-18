<?php

namespace Modules\Auth\Enums;

use Modules\Auth\Forms\EmailChangeForm;
use Modules\Auth\Forms\PasswordForm;
use Modules\Auth\Forms\PersonalInfoForm;
use Modules\Auth\Forms\PhoneChangeForm;
use Modules\Auth\Forms\PreferencesForm;
use Modules\Auth\Lists\UserAddressListing;
use Modules\Auth\Models\User;
use Modules\Auth\Support\AuthChannels;
use Modules\Form\Support\Form;
use Modules\Form\Support\Listing;

/**
 * Les liens possibles du menu du profil : chacun ouvre soit un formulaire, que
 * l'app affiche et envoie selon sa définition (`Form::submission()`), soit une
 * liste (`Listing`), sans les connaître d'avance. Ajouter un lien = un cas ici,
 * son formulaire ou sa liste et ses routes d'API, sans nouvelle version de l'app
 * (décisions 2026-11-17-formulaires-profil-generiques et
 * 2026-11-18-adresses-liste-generique).
 * L'ordre du menu est fixé par `ProfileMenuService`.
 */
enum ProfileMenuKey: string
{
    case PERSONAL = 'personal';
    case EMAIL = 'email';
    case PHONE = 'phone';
    case PASSWORD = 'password';
    case PREFERENCES = 'preferences';
    case ADDRESSES = 'addresses';

    /**
     * Formulaire ouvert par le lien, ou `null` pour un lien vers une liste.
     *
     * @return class-string<Form>|null
     */
    public function formClass(): ?string
    {
        return match ($this) {
            self::PERSONAL => PersonalInfoForm::class,
            self::EMAIL => EmailChangeForm::class,
            self::PHONE => PhoneChangeForm::class,
            self::PASSWORD => PasswordForm::class,
            self::PREFERENCES => PreferencesForm::class,
            default => null,
        };
    }

    /**
     * Liste ouverte par le lien, ou `null` pour un lien vers un formulaire.
     *
     * @return class-string<Listing>|null
     */
    public function listingClass(): ?string
    {
        return match ($this) {
            self::ADDRESSES => UserAddressListing::class,
            default => null,
        };
    }

    /**
     * Nom du formulaire dans l'API (`auth.profile.personal`), que l'app ouvre.
     */
    public function form(): ?string
    {
        $class = $this->formClass();

        return $class === null ? null : $class::name();
    }

    /**
     * Nom de la liste dans l'API (`auth.addresses`), que l'app ouvre.
     */
    public function list(): ?string
    {
        $class = $this->listingClass();

        return $class === null ? null : $class::name();
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
            self::ADDRESSES => 'addresses',
        });
    }
}
