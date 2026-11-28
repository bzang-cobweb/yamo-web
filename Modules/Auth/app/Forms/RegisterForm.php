<?php

namespace Modules\Auth\Forms;

use App\Models\Setting;
use Bza\Form\Support\Field;
use Bza\Form\Support\Form;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Validation\Rule;
use Modules\Auth\Models\User;
use Modules\Auth\Rules\UniquePhone;
use Modules\Auth\Support\AuthChannels;
use Modules\Auth\Support\PhoneNumbers;
use Modules\Location\Support\CurrentCountry;

/**
 * Créer son compte, dernière étape du parcours (décision 2026-11-28-connexion-panneau) : l'identifiant est prouvé par
 * le code, le mot de passe choisi ; restent le prénom, le nom, puis l'autre moyen de contact, facultatif : le
 * téléphone pour une inscription par e-mail (`?channel=email`), l'e-mail pour une inscription par téléphone
 * (`?channel=phone`). Le pseudonyme est tiré du prénom et du nom par le serveur. L'app l'envoie à
 * `POST /auth/register` avec le défi.
 */
class RegisterForm extends Form
{
    /** Le canal de l'identifiant prouvé (`email` ou `phone`) ; `null` : pas d'autre contact. */
    private readonly ?string $channel;

    public function __construct(?Authenticatable $user = null, ?string $channel = null)
    {
        parent::__construct($user);

        // Servi par `GET /forms/auth.register?channel=…` ; `sms` vaut `phone` (le canal d'un défi).
        $channel ??= request()->query('channel');
        $this->channel = match ($channel) {
            'email' => 'email',
            'phone', 'sms' => 'phone',
            default => null,
        };
    }

    public static function name(): string
    {
        return 'auth.register';
    }

    public function fields(): array
    {
        return [
            Field::text('first_name')->label(__('auth::user.first_name'))->required()->rules(['max:255']),
            Field::text('last_name')->label(__('auth::user.last_name'))->required()->rules(['max:255']),
            // La date de naissance reste au profil ; avec un âge minimum réglé, elle est exigée ici (décision
            // 2026-11-19-age-minimum), la même que dans les informations personnelles.
            ...(Setting::minimumAge() !== null ? [PersonalInfoForm::birthDateField()] : []),
            ...$this->otherContactFields(),
        ];
    }

    /**
     * L'autre moyen de contact, facultatif et unique, si son canal est ouvert dans l'application.
     *
     * @return list<Field>
     */
    private function otherContactFields(): array
    {
        if ($this->channel === 'email' && AuthChannels::phone()) {
            return [
                Field::phone('phone')->label(__('auth::user.phone'))->options(PhoneNumbers::countryOptions())
                    // Saisi sans preuve : refusé dès qu'un autre compte le porte (décision 2026-11-28-numero-non-verifie).
                    ->rules([new UniquePhone(proven: false)])
                    // Le pays courant d'office (décision 2026-11-18-espace-pays) ; l'app peut reprendre le dernier indicatif.
                    ->default(['country' => CurrentCountry::get()?->code, 'number' => null]),
            ];
        }

        if ($this->channel === 'phone' && AuthChannels::email()) {
            return [
                Field::email('email')->label(__('auth::user.email'))->rules(['max:255', Rule::unique(User::class, 'email')]),
            ];
        }

        return [];
    }

    public function title(): string
    {
        return __('auth::form.register.title');
    }

    public function submitLabel(): string
    {
        return __('auth::form.register.submit');
    }

    public function canBeViewedBy(?Authenticatable $user): bool
    {
        return true;
    }
}
