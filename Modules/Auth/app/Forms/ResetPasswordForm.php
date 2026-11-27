<?php

namespace Modules\Auth\Forms;

use Bza\Form\Support\Field;
use Bza\Form\Support\Form;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Validation\Rules\Password;

/**
 * Mot de passe oublié, dernière étape : le nouveau mot de passe et sa confirmation,
 * une fois le code validé.
 */
class ResetPasswordForm extends Form
{
    public static function name(): string
    {
        return 'auth.password.reset';
    }

    public function fields(): array
    {
        return [
            Field::password('password')
                ->label(__('auth::user.new_password'))
                ->help(__('auth::form.password.help'))
                ->required()
                ->rules([Password::defaults(), 'confirmed']),
            Field::password('password_confirmation')->label(__('auth::user.password_confirmation_plain'))->required(),
        ];
    }

    public function title(): string
    {
        return __('auth::form.password_reset.title');
    }

    public function submitLabel(): string
    {
        return __('auth::form.password_reset.submit');
    }

    public function canBeViewedBy(?Authenticatable $user): bool
    {
        return true;
    }
}
