<?php

namespace Modules\Auth\Forms;

use Bza\Form\Support\Field;
use Bza\Form\Support\Form;
use Bza\Form\Support\Step;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Modules\Auth\Enums\GenderEnum;
use Modules\Auth\Models\User;
use Modules\Auth\Rules\UniquePhone;
use Modules\Auth\Support\AuthChannels;
use Modules\Auth\Support\PhoneNumbers;
use Modules\Location\Support\CurrentCountry;

/**
 * Créer un compte depuis l'app, en trois étapes : qui (pseudonyme, prénom, nom,
 * genre, date de naissance et description facultatives), comment joindre (e-mail et/ou téléphone, selon les canaux de l'application), mot de passe. Chaque étape est
 * validée avant la suivante, l'unicité comprise ; l'envoi final valide tout. Un code
 * part vers chaque identifiant, et le compte n'existe qu'une fois tous validés.
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
            Field::select('gender')
                ->label(__('auth::user.gender'))
                ->options(GenderEnum::options())
                ->required(),
            // La même que dans les informations personnelles (âge minimum compris).
            PersonalInfoForm::birthDateField(),
            Field::textarea('description')->label(__('auth::user.description')),
            ...$this->contactFields(),
            Field::password('password')
                ->label(__('auth::user.password'))
                ->help(__('auth::form.password.help'))
                ->required()
                ->rules([Password::defaults(), 'confirmed']),
            Field::password('password_confirmation')->label(__('auth::user.password_confirmation_plain'))->required(),
        ];
    }

    /**
     * Comment joindre le compte, selon les canaux de l'application : l'un ou
     * l'autre (ou les deux) quand les deux sont actifs, sinon celui qui l'est,
     * obligatoire.
     *
     * @return list<Field>
     */
    private function contactFields(): array
    {
        $both = AuthChannels::both();
        $fields = [];

        if (AuthChannels::email()) {
            $fields[] = Field::email('email')
                ->label(__('auth::user.email'))
                ->required(! $both)
                ->rules([...($both ? ['required_without:phone'] : []), 'max:255', Rule::unique(User::class, 'email')]);
        }

        if (AuthChannels::phone()) {
            $fields[] = Field::phone('phone')
                ->label(__('auth::user.phone'))
                ->options(PhoneNumbers::countryOptions())
                // Le pays courant d'office (décision 2026-11-18-espace-pays).
                ->default(['country' => CurrentCountry::get()?->code, 'number' => null])
                ->required(! $both)
                ->rules([...($both ? ['required_without:email'] : []), new UniquePhone]);
        }

        return $fields;
    }

    public function steps(): array
    {
        return [
            Step::make(__('auth::form.register.steps.identity'), ['name', 'first_name', 'last_name', 'gender', 'birth_date', 'description']),
            // L'un ou l'autre (ou les deux) : expliqué sous le titre de l'étape.
            Step::make(__('auth::form.register.steps.contact'), array_keys(array_filter(AuthChannels::enabled())))
                ->subtitle(AuthChannels::both() ? __('auth::form.register.contact_help') : null),
            Step::make(__('auth::form.register.steps.password'), ['password', 'password_confirmation']),
        ];
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
