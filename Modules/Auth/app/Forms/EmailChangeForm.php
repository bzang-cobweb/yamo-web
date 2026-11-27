<?php

namespace Modules\Auth\Forms;

use Bza\Form\Enums\SuccessAction;
use Bza\Form\Support\Field;
use Bza\Form\Support\Form;
use Bza\Form\Support\FormLink;
use Bza\Form\Support\Submission;
use Illuminate\Contracts\Auth\Authenticatable;
use Modules\Auth\Models\User;
use Modules\Auth\Support\AuthChannels;
use Modules\Auth\Support\Masks;

/**
 * Ajouter ou changer son adresse e-mail : l'actuelle, masquée, en lecture seule (s'il y en
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
            // L'adresse actuelle, masquée (décision 2026-11-27-email-telephone-masques).
            $current === null ? null : Field::text('current_email')->label(__('auth::user.current_email'))->default(Masks::email($current))
                ->help($this->user instanceof User && $this->user->email_verified_at === null ? __('auth::form.email.not_verified') : null)
                ->readonly(),
            Field::email('email')->label(__('auth::user.new_email'))->required()->rules(['max:255']),
        ]));
    }

    /**
     * L'adresse actuelle non vérifiée : « Vérifier mon adresse » (décision 2026-11-27-verifier-email-telephone).
     */
    public function links(): array
    {
        if (! $this->user instanceof User || $this->user->email === null || $this->user->email_verified_at !== null) {
            return [];
        }

        return [FormLink::make(__('auth::form.email.verify_link'), EmailVerifyForm::name())];
    }

    public function title(): string
    {
        return __($this->user instanceof User && $this->user->email !== null ? 'auth::form.email.change' : 'auth::form.email.add');
    }

    /**
     * L'app envoie le formulaire à `api.v1.auth.me.email.store`
     * (décision 2026-11-17-formulaires-profil-generiques).
     */
    public function submission(): Submission
    {
        return Submission::toRoute('api.v1.auth.me.email.store', SuccessAction::PROFILE, __('auth::mobile.profile.emailSaved'));
    }

    public function submitLabel(): string
    {
        return __('auth::form.code.send');
    }

    /**
     * Un compte connecté, si le canal est actif dans l'application.
     */
    public function canBeViewedBy(?Authenticatable $user): bool
    {
        return $user !== null && AuthChannels::email();
    }
}
