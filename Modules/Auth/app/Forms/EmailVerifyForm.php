<?php

namespace Modules\Auth\Forms;

use Bza\Form\Enums\SuccessAction;
use Bza\Form\Support\Field;
use Bza\Form\Support\Form;
use Bza\Form\Support\Submission;
use Illuminate\Contracts\Auth\Authenticatable;
use Modules\Auth\Models\User;
use Modules\Auth\Support\AuthChannels;
use Modules\Auth\Support\Masks;

/**
 * Vérifier son adresse e-mail actuelle (décision 2026-11-27-verifier-email-telephone) : ouvert par le lien « Vérifier
 * mon adresse » sous « Changer d'adresse e-mail » quand elle n'est pas vérifiée. L'adresse, masquée, en lecture
 * seule ; « Envoyer le code » y envoie un code, dont la validation la marque vérifiée.
 */
class EmailVerifyForm extends Form
{
    public static function name(): string
    {
        return 'auth.profile.email.verify';
    }

    public function fields(): array
    {
        $email = $this->user instanceof User ? $this->user->email : null;

        return [
            Field::text('email')->label(__('auth::user.current_email'))->default(Masks::email($email))->readonly(),
        ];
    }

    public function title(): string
    {
        return __('auth::form.email.verify_title');
    }

    public function subtitle(): string
    {
        return __('auth::form.email.verify_subtitle');
    }

    public function submission(): Submission
    {
        return Submission::toRoute('api.v1.auth.me.email.verify', SuccessAction::PROFILE, __('auth::form.email.verified'));
    }

    public function submitLabel(): string
    {
        return __('auth::form.code.send');
    }

    /**
     * Un compte connecté qui a une adresse, si le canal est actif dans l'application.
     */
    public function canBeViewedBy(?Authenticatable $user): bool
    {
        return $user instanceof User && $user->email !== null && AuthChannels::email();
    }
}
