<?php

namespace Modules\Auth\Forms;

use Bza\Form\Enums\SuccessAction;
use Bza\Form\Support\Form;
use Bza\Form\Support\Submission;
use Modules\Auth\Models\User;

/**
 * « Adresse de résidence », dans « Informations personnelles » (`PUT /api/v1/auth/me/residence`, décision
 * 2026-11-27-adresse-de-residence) : les champs d'une adresse du compte (`UserAddressForm::addressFields()`), sans le
 * destinataire ; préremplis par l'adresse de résidence enregistrée, qui est créée ou modifiée.
 */
class ResidenceForm extends Form
{
    public static function name(): string
    {
        return 'auth.profile.residence';
    }

    public function fields(): array
    {
        $residence = $this->user instanceof User ? $this->user->residence()->with('address')->first() : null;

        return UserAddressForm::addressFields($residence);
    }

    public function title(): string
    {
        return __('auth::residence.title');
    }

    public function notice(): ?string
    {
        return __('auth::residence.notice');
    }

    public function submission(): Submission
    {
        return Submission::toRoute('api.v1.auth.me.residence.update', SuccessAction::PROFILE, __('auth::residence.saved'));
    }

    public function submitLabel(): string
    {
        return __('auth::form.save');
    }
}
