<?php

namespace Modules\Auth\Forms;

use Illuminate\Contracts\Auth\Authenticatable;
use Modules\Form\Support\Field;
use Modules\Form\Support\Form;

/**
 * Saisir le code reçu par e-mail ou SMS. Commun à la connexion, à l'inscription
 * et aux changements d'e-mail ou de téléphone ; l'app y joint le défi.
 */
class CodeForm extends Form
{
    public static function name(): string
    {
        return 'auth.code';
    }

    public function fields(): array
    {
        return [
            Field::code('code')->label(__('auth::form.code.label'))->required(),
        ];
    }

    public function title(): string
    {
        return __('auth::form.code.title');
    }

    public function submitLabel(): string
    {
        return __('auth::form.code.submit');
    }

    public function canBeViewedBy(?Authenticatable $user): bool
    {
        return true;
    }
}
