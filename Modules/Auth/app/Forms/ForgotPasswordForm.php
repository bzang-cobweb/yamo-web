<?php

namespace Modules\Auth\Forms;

use Bza\Form\Support\Form;
use Illuminate\Contracts\Auth\Authenticatable;
use Modules\Auth\Forms\Concerns\HasIdentifierField;
use Modules\Auth\Support\AuthChannels;

/**
 * Mot de passe oublié, première étape : l'e-mail ou le téléphone du compte. Un code
 * y part si un compte correspond ; la réponse est la même dans tous les cas.
 */
class ForgotPasswordForm extends Form
{
    use HasIdentifierField;

    public static function name(): string
    {
        return 'auth.password.forgot';
    }

    public function fields(): array
    {
        return [
            ...$this->identifierFields(),
        ];
    }

    public function title(): string
    {
        return __('auth::form.password_forgot.title');
    }

    public function subtitle(): string
    {
        return match (true) {
            AuthChannels::both() => __('auth::form.password_forgot.subtitle'),
            AuthChannels::email() => __('auth::form.password_forgot.subtitle_email'),
            default => __('auth::form.password_forgot.subtitle_phone'),
        };
    }

    public function submitLabel(): string
    {
        return __('auth::form.code.send');
    }

    public function canBeViewedBy(?Authenticatable $user): bool
    {
        return true;
    }
}
