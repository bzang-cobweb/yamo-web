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
 * Vérifier son numéro actuel (décision 2026-11-27-verifier-email-telephone) : ouvert par le lien « Renvoyer le code »
 * sous « Changer de numéro » quand il n'est pas vérifié. Le numéro, masqué, en lecture seule ; « Envoyer le code » y
 * envoie un SMS, dont la validation le marque vérifié.
 */
class PhoneVerifyForm extends Form
{
    public static function name(): string
    {
        return 'auth.profile.phone.verify';
    }

    public function fields(): array
    {
        $phone = $this->user instanceof User ? $this->user->phone : null;

        return [
            Field::text('phone')->label(__('auth::user.current_phone'))->default(Masks::phone($phone))->readonly(),
        ];
    }

    public function title(): string
    {
        return __('auth::form.phone.verify_title');
    }

    public function subtitle(): string
    {
        return __('auth::form.phone.verify_subtitle');
    }

    public function submission(): Submission
    {
        return Submission::toRoute('api.v1.auth.me.phone.verify', SuccessAction::PROFILE, __('auth::form.phone.verified'));
    }

    public function submitLabel(): string
    {
        return __('auth::form.code.send');
    }

    /**
     * Un compte connecté qui a un numéro, si le canal est actif dans l'application.
     */
    public function canBeViewedBy(?Authenticatable $user): bool
    {
        return $user instanceof User && $user->phone !== null && AuthChannels::phone();
    }
}
