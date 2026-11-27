<?php

namespace Modules\Auth\Forms;

use Bza\Form\Support\Field;
use Bza\Form\Support\Form;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Validation\Rule;
use Modules\Auth\Models\User;
use Modules\Auth\Rules\UniquePhone;
use Modules\Auth\Support\AuthChannels;
use Modules\Auth\Support\PhoneNumbers;

/**
 * Créer un compte depuis l'app, sur une seule page : pseudonyme, prénom, nom et date de
 * naissance (décision 2026-11-27-connexion-sans-mot-de-passe). L'identifiant vient de
 * la connexion : l'app renvoie l'e-mail, ou le numéro et son pays, saisis à la
 * connexion (champs cachés). Pas de mot de passe. Un code part vers cet identifiant,
 * et le compte n'existe qu'une fois le code validé.
 */
class RegisterForm extends Form
{
    public static function name(): string
    {
        return 'auth.register';
    }

    public function fields(): array
    {
        return [
            Field::text('name')
                ->label(__('auth::user.name'))
                ->help(__('auth::user.name_help'))
                ->required()
                ->rules(['max:255', Rule::unique(User::class, 'name')]),
            Field::text('first_name')->label(__('auth::user.first_name'))->required()->rules(['max:255']),
            Field::text('last_name')->label(__('auth::user.last_name'))->required()->rules(['max:255']),
            // La même que dans les informations personnelles (âge minimum compris).
            PersonalInfoForm::birthDateField(),
            ...$this->contactFields(),
        ];
    }

    /**
     * L'identifiant saisi à la connexion, caché : l'e-mail, ou le numéro et son pays,
     * selon les canaux de l'application ; l'un des deux est obligatoire.
     *
     * @return list<Field>
     */
    private function contactFields(): array
    {
        $fields = [];

        if (AuthChannels::email()) {
            $fields[] = Field::hidden('email')->rules([
                AuthChannels::phone() ? 'required_without:phone' : 'required',
                'nullable', 'email', 'max:255', Rule::unique(User::class, 'email'),
            ]);
        }

        if (AuthChannels::phone()) {
            $fields[] = Field::hidden('phone')->rules([
                AuthChannels::email() ? 'required_without:email' : 'required',
                'nullable', 'string', 'phone', new UniquePhone,
            ]);
            $fields[] = Field::hidden('phone_country')->rules([
                'required_with:phone', 'nullable', 'string',
                Rule::in(array_map('strval', array_keys(PhoneNumbers::countryOptions()))),
            ]);
        }

        return $fields;
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
