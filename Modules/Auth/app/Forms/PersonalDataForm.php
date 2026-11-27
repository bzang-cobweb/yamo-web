<?php

namespace Modules\Auth\Forms;

use Bza\Form\Enums\SuccessAction;
use Bza\Form\Support\Form;
use Bza\Form\Support\Submission;

/**
 * « Mes données », dans « Confidentialité » (`POST /api/v1/auth/me/personal-data`, décision
 * 2026-11-27-confidentialite) : aucun champ, une explication et « Demander mes données ». Un fichier JSON des données
 * du compte part à son adresse e-mail vérifiée.
 */
class PersonalDataForm extends Form
{
    public static function name(): string
    {
        return 'auth.profile.privacy.data';
    }

    public function fields(): array
    {
        return [];
    }

    public function title(): string
    {
        return __('auth::privacy.data.title');
    }

    public function subtitle(): string
    {
        return __('auth::privacy.data.subtitle');
    }

    public function submission(): Submission
    {
        return Submission::toRoute('api.v1.auth.me.personal-data.store', SuccessAction::PROFILE, __('auth::privacy.data.sent'));
    }

    public function submitLabel(): string
    {
        return __('auth::privacy.data.submit');
    }
}
