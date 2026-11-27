<?php

namespace Modules\Auth\Forms;

use Bza\Form\Enums\SuccessAction;
use Bza\Form\Support\Field;
use Bza\Form\Support\Form;
use Bza\Form\Support\Submission;
use Modules\Auth\Models\User;
use Modules\Auth\Support\PrivacySettings;

/**
 * « Avis », dans « Confidentialité » (`PATCH /api/v1/auth/me/privacy/reviews`, décision 2026-11-27-confidentialite) :
 * un interrupteur, montrer son pays, sa région et sa ville sous ses avis. Non par défaut.
 */
class ReviewPrivacyForm extends Form
{
    public static function name(): string
    {
        return 'auth.profile.privacy.reviews';
    }

    public function fields(): array
    {
        $settings = $this->user instanceof User ? $this->user->privacySettings() : PrivacySettings::defaults();

        return [
            Field::switch(PrivacySettings::REVIEW_LOCATION)
                ->label(__('auth::privacy.reviews.label'))
                ->help(__('auth::privacy.reviews.help'))
                ->default($settings[PrivacySettings::REVIEW_LOCATION]),
        ];
    }

    public function title(): string
    {
        return __('auth::privacy.reviews.title');
    }

    public function submission(): Submission
    {
        return Submission::toRoute('api.v1.auth.me.privacy.reviews.update', SuccessAction::PROFILE, __('auth::mobile.profile.saved'));
    }

    public function submitLabel(): string
    {
        return __('auth::form.save');
    }
}
