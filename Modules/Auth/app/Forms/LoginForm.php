<?php

namespace Modules\Auth\Forms;

use Illuminate\Contracts\Auth\Authenticatable;
use Modules\Form\Support\Field;
use Modules\Form\Support\Form;

/**
 * Connexion de l'app mobile. `device_name` n'en fait pas partie : l'app le
 * renseigne elle-même (décision 2026-11-15-contrat-mobile-features-formulaires, D).
 */
class LoginForm extends Form
{
    public static function name(): string
    {
        return 'auth.login';
    }

    public function fields(): array
    {
        return [
            Field::email('email')
                ->label(__('auth::user.email'))
                ->required()
                ->rules(['max:255']),
            Field::password('password')
                ->label(__('auth::user.password'))
                ->required()
                ->rules(['max:255']),
        ];
    }

    public function submitLabel(): string
    {
        return __('auth::form.login.submit');
    }

    /**
     * Public : c'est le formulaire qu'on remplit avant d'être connecté.
     */
    public function canBeViewedBy(?Authenticatable $user): bool
    {
        return true;
    }
}
