<?php

namespace Modules\Auth\Forms;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Modules\Auth\Enums\GenderEnum;
use Modules\Auth\Models\User;
use Modules\Auth\Rules\UniquePhone;
use Modules\Auth\Support\PhoneNumbers;
use Modules\Form\Support\Field;
use Modules\Form\Support\Form;
use Modules\Form\Support\Step;

/**
 * Créer un compte depuis l'app, en trois étapes : qui (pseudonyme, prénom, nom,
 * genre, description facultative), comment joindre (e-mail et/ou téléphone), mot de passe. Chaque étape est
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
            Field::textarea('description')->label(__('auth::user.description')),
            Field::email('email')
                ->label(__('auth::user.email'))
                ->help(__('auth::form.register.contact_help'))
                ->rules(['required_without:phone', 'max:255', Rule::unique(User::class, 'email')]),
            Field::phone('phone')
                ->label(__('auth::user.phone'))
                ->options(PhoneNumbers::countryOptions())
                ->rules(['required_without:email', new UniquePhone]),
            Field::password('password')
                ->label(__('auth::user.password'))
                ->help(__('auth::form.password.help'))
                ->required()
                ->rules([Password::defaults(), 'confirmed']),
            Field::password('password_confirmation')->label(__('auth::user.password_confirmation_plain'))->required(),
        ];
    }

    public function steps(): array
    {
        return [
            Step::make(__('auth::form.register.steps.identity'), ['name', 'first_name', 'last_name', 'gender', 'description']),
            Step::make(__('auth::form.register.steps.contact'), ['email', 'phone']),
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
