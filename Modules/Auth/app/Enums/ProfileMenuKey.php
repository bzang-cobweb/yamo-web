<?php

namespace Modules\Auth\Enums;

use Bza\Form\Support\Form;
use Bza\Form\Support\Listing;
use Modules\Auth\Forms\DeleteAccountForm;
use Modules\Auth\Forms\EmailChangeForm;
use Modules\Auth\Forms\IdentityForm;
use Modules\Auth\Forms\NotificationSettingsForm;
use Modules\Auth\Forms\PasswordForm;
use Modules\Auth\Forms\PersonalInfoForm;
use Modules\Auth\Forms\PhoneChangeForm;
use Modules\Auth\Forms\PreferencesForm;
use Modules\Auth\Forms\ResidenceForm;
use Modules\Auth\Lists\UserAddressListing;
use Modules\Auth\Models\User;
use Modules\Auth\Support\AuthChannels;

/**
 * Les liens possibles du menu du profil : chacun ouvre soit un formulaire, que
 * l'app affiche et envoie selon sa définition (`Form::submission()`), soit une
 * liste (`Listing`), soit un sous-menu (`menu()`), sans les connaître d'avance.
 * Ajouter un lien = un cas ici, son formulaire ou sa liste et ses routes d'API, sans
 * nouvelle version de l'app (décisions 2026-11-17-formulaires-profil-generiques,
 * 2026-11-18-adresses-liste-generique et 2026-11-27-menu-profil-icones-sous-menus).
 * L'ordre du menu et de ses sous-menus est fixé par `ProfileMenuService`.
 */
enum ProfileMenuKey: string
{
    /** « Informations personnelles » : un sous-menu. */
    case PERSONAL = 'personal';
    /** Les informations elles-mêmes (prénom, nom, date de naissance, pays, région, description). */
    case DETAILS = 'details';
    case IDENTITY = 'identity';
    case EMAIL = 'email';
    case PHONE = 'phone';
    case PASSWORD = 'password';
    /** L'adresse de résidence (décision 2026-11-27-adresse-de-residence). */
    case RESIDENCE = 'residence';
    /** « Confidentialité » : un sous-menu. */
    case PRIVACY = 'privacy';
    case DELETE_ACCOUNT = 'delete-account';
    /** Les choix de notifications (décision 2026-11-27-notifications-du-profil). */
    case NOTIFICATIONS = 'notifications';
    case PREFERENCES = 'preferences';
    case ADDRESSES = 'addresses';

    /**
     * Formulaire ouvert par le lien, ou `null` pour un lien vers une liste ou un sous-menu.
     *
     * @return class-string<Form>|null
     */
    public function formClass(): ?string
    {
        return match ($this) {
            self::DETAILS => PersonalInfoForm::class,
            self::IDENTITY => IdentityForm::class,
            self::EMAIL => EmailChangeForm::class,
            self::PHONE => PhoneChangeForm::class,
            self::PASSWORD => PasswordForm::class,
            self::RESIDENCE => ResidenceForm::class,
            self::DELETE_ACCOUNT => DeleteAccountForm::class,
            self::NOTIFICATIONS => NotificationSettingsForm::class,
            self::PREFERENCES => PreferencesForm::class,
            default => null,
        };
    }

    /**
     * Liste ouverte par le lien, ou `null`.
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
     * Le sous-menu ouvert par le lien (`GET /me/menu/{menu}`), ou `null`.
     */
    public function menu(): ?string
    {
        return match ($this) {
            self::PERSONAL, self::PRIVACY => $this->value,
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
     * La cible servie : `form`, `list` ou `menu`.
     *
     * @return array{form: string}|array{list: string}|array{menu: string}
     */
    public function target(): array
    {
        return match (true) {
            $this->form() !== null => ['form' => $this->form()],
            $this->menu() !== null => ['menu' => $this->menu()],
            default => ['list' => (string) $this->list()],
        };
    }

    /**
     * Le symbole devant le libellé : SF Symbols sur iOS, Material ailleurs
     * (décision 2026-11-27-menu-profil-icones-sous-menus).
     *
     * @return array{ios: string, android: string}
     */
    public function icon(): array
    {
        [$ios, $android] = match ($this) {
            self::PERSONAL => ['person.crop.circle', 'account_circle'],
            self::DETAILS => ['person.text.rectangle', 'badge'],
            self::IDENTITY => ['checkmark.shield', 'verified_user'],
            self::EMAIL => ['envelope', 'mail'],
            self::PHONE => ['phone', 'call'],
            self::PASSWORD => ['key', 'key'],
            self::RESIDENCE => ['house', 'home'],
            self::PRIVACY => ['lock', 'lock'],
            self::DELETE_ACCOUNT => ['trash', 'delete'],
            self::NOTIFICATIONS => ['bell', 'notifications'],
            self::PREFERENCES => ['gearshape', 'settings'],
            self::ADDRESSES => ['mappin.and.ellipse', 'location_on'],
        };

        return ['ios' => $ios, 'android' => $android];
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
            self::DETAILS => 'details',
            self::IDENTITY => 'identity',
            self::EMAIL => filled($user->email) ? 'changeEmail' : 'addEmail',
            self::PHONE => filled($user->phone) ? 'changePhone' : 'addPhone',
            self::PASSWORD => 'password',
            self::RESIDENCE => 'residence',
            self::PRIVACY => 'privacy',
            self::DELETE_ACCOUNT => 'deleteAccount',
            self::NOTIFICATIONS => 'notifications',
            self::PREFERENCES => 'preferences',
            self::ADDRESSES => 'addresses',
        });
    }
}
