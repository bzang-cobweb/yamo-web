<?php

namespace Modules\Auth\Forms;

use Modules\Auth\Models\User;
use Modules\Form\Support\Field;
use Modules\Form\Support\Form;

/**
 * Informations personnelles, depuis le profil de l'app mobile
 * (`PATCH /api/v1/auth/me/personal-info`) : prénom et nom.
 */
class PersonalInfoForm extends Form
{
    public static function name(): string
    {
        return 'auth.profile.personal';
    }

    public function fields(): array
    {
        $user = $this->user instanceof User ? $this->user : null;

        return [
            Field::text('first_name')
                ->label(__('auth::user.first_name'))
                ->default($user?->first_name)
                ->required()
                ->rules(['max:255']),
            Field::text('last_name')
                ->label(__('auth::user.last_name'))
                ->default($user?->last_name)
                ->required()
                ->rules(['max:255']),
        ];
    }

    public function title(): string
    {
        return __('auth::form.personal.title');
    }

    public function submitLabel(): string
    {
        return __('auth::form.save');
    }
}
