<?php

namespace Modules\Auth\Forms;

use Illuminate\Contracts\Auth\Authenticatable;
use Modules\Auth\Forms\Concerns\HasIdentifierField;
use Modules\Form\Support\Field;
use Modules\Form\Support\Form;

/**
 * Connexion de l'app mobile, première étape : e-mail ou téléphone (format
 * international) et mot de passe. Un code est ensuite envoyé à cet e-mail ou ce
 * téléphone (formulaire `auth.code`). `device_name` part avec le code.
 */
class LoginForm extends Form
{
    use HasIdentifierField;

    public static function name(): string
    {
        return 'auth.login';
    }

    public function fields(): array
    {
        return [
            $this->identifierField(),
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

    public function title(): string
    {
        return __('auth::form.login.title');
    }

    public function subtitle(): string
    {
        return __('auth::form.login.subtitle');
    }

    /**
     * Public : c'est le formulaire qu'on remplit avant d'être connecté.
     */
    public function canBeViewedBy(?Authenticatable $user): bool
    {
        return true;
    }
}
