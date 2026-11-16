<?php

namespace Modules\Auth\Forms;

use Modules\Auth\Models\User;
use Modules\Form\Support\Field;
use Modules\Form\Support\Form;

/**
 * Ajouter ou changer son adresse e-mail : l'actuelle en lecture seule (s'il y en
 * a une), la nouvelle à saisir. Un code part vers la nouvelle adresse ; rien ne
 * change avant sa validation.
 */
class EmailChangeForm extends Form
{
    public static function name(): string
    {
        return 'auth.profile.email';
    }

    public function fields(): array
    {
        $current = $this->user instanceof User ? $this->user->email : null;

        return array_values(array_filter([
            $current === null ? null : Field::email('current_email')->label(__('auth::user.current_email'))->default($current)->readonly(),
            Field::email('email')->label(__('auth::user.new_email'))->required()->rules(['max:255']),
        ]));
    }

    public function title(): string
    {
        return __($this->user instanceof User && $this->user->email !== null ? 'auth::form.email.change' : 'auth::form.email.add');
    }

    public function submitLabel(): string
    {
        return __('auth::form.code.send');
    }
}
