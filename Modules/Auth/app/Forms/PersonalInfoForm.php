<?php

namespace Modules\Auth\Forms;

use Modules\Auth\Enums\GenderEnum;
use Modules\Auth\Models\User;
use Modules\Form\Support\Field;
use Modules\Form\Support\Form;

/**
 * Informations personnelles, depuis le profil de l'app mobile
 * (`PATCH /api/v1/auth/me/personal-info`) : prénom, nom, genre (obligatoire) et
 * date de naissance (facultative).
 */
class PersonalInfoForm extends Form
{
    /** Date de naissance la plus ancienne acceptée (aussi par le back-office). */
    public const BIRTH_DATE_MIN = '1900-01-01';

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
            Field::radio('gender')
                ->label(__('auth::user.gender'))
                ->options(GenderEnum::options())
                ->default($user?->gender?->value)
                ->required(),
            // Facultative : `required` à faux, la règle devient `nullable`.
            Field::date('birth_date')
                ->label(__('auth::user.birth_date'))
                ->default($user?->birth_date?->format('Y-m-d'))
                ->rules(['after_or_equal:'.self::BIRTH_DATE_MIN, 'before_or_equal:today']),
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
