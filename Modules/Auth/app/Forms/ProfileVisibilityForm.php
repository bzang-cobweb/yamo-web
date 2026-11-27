<?php

namespace Modules\Auth\Forms;

use Bza\Form\Enums\SuccessAction;
use Bza\Form\Support\Field;
use Bza\Form\Support\Form;
use Bza\Form\Support\Submission;
use Modules\Auth\Models\User;
use Modules\Auth\Support\PrivacySettings;

/**
 * « Visibilité du profil », dans « Confidentialité » (`PATCH /api/v1/auth/me/privacy/visibility`, décision
 * 2026-11-27-confidentialite) : un choix unique, en boutons radio, parmi tout le monde, les propriétaires, mes contacts,
 * les propriétaires et mes contacts. Tout le monde par défaut.
 */
class ProfileVisibilityForm extends Form
{
    public static function name(): string
    {
        return 'auth.profile.privacy.visibility';
    }

    public function fields(): array
    {
        $settings = $this->user instanceof User ? $this->user->privacySettings() : PrivacySettings::defaults();

        return [
            Field::radio(PrivacySettings::VISIBILITY)
                ->label(__('auth::privacy.visibility.label'))
                ->help(__('auth::privacy.visibility.help'))
                ->options(collect(PrivacySettings::VISIBILITIES)->mapWithKeys(fn (string $value): array => [$value => __('auth::privacy.visibility.options.'.$value)])->all())
                ->default($settings[PrivacySettings::VISIBILITY])
                ->required(),
        ];
    }

    public function title(): string
    {
        return __('auth::privacy.visibility.title');
    }

    public function subtitle(): string
    {
        return __('auth::privacy.visibility.subtitle');
    }

    public function submission(): Submission
    {
        return Submission::toRoute('api.v1.auth.me.privacy.visibility.update', SuccessAction::PROFILE, __('auth::mobile.profile.saved'));
    }

    public function submitLabel(): string
    {
        return __('auth::form.save');
    }
}
