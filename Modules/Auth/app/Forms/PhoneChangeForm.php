<?php

namespace Modules\Auth\Forms;

use Bza\Form\Enums\SuccessAction;
use Bza\Form\Support\Field;
use Bza\Form\Support\Form;
use Bza\Form\Support\Submission;
use Illuminate\Contracts\Auth\Authenticatable;
use Modules\Auth\Models\User;
use Modules\Auth\Support\AuthChannels;
use Modules\Auth\Support\PhoneNumbers;
use Modules\Location\Support\CurrentCountry;

/**
 * Ajouter ou changer son numéro : l'actuel (indicatif et numéro) en lecture seule
 * s'il y en a un, le nouveau à saisir (pays + numéro). Un code part vers le nouveau
 * numéro ; rien ne change avant sa validation.
 */
class PhoneChangeForm extends Form
{
    public static function name(): string
    {
        return 'auth.profile.phone';
    }

    public function fields(): array
    {
        $user = $this->user instanceof User ? $this->user : null;
        $options = PhoneNumbers::countryOptions();
        // Le pays courant d'office (décision 2026-11-18-espace-pays).
        $defaultCountry = CurrentCountry::get()?->code ?? $user?->phoneCountry?->code ?? $user?->country?->code;

        return array_values(array_filter([
            $user?->phone === null ? null : Field::phone('current_phone')
                ->label(__('auth::user.current_phone'))
                ->options($options)
                ->default(['country' => $user->phoneCountry?->code, 'number' => PhoneNumbers::national($user->phone)])
                ->readonly(),
            Field::phone('phone')
                ->label(__('auth::user.new_phone'))
                ->options($options)
                ->default(['country' => $defaultCountry, 'number' => null])
                ->required(),
        ]));
    }

    public function title(): string
    {
        return __($this->user instanceof User && $this->user->phone !== null ? 'auth::form.phone.change' : 'auth::form.phone.add');
    }

    /**
     * L'app envoie le formulaire à `api.v1.auth.me.phone.store`
     * (décision 2026-11-17-formulaires-profil-generiques).
     */
    public function submission(): Submission
    {
        return Submission::toRoute('api.v1.auth.me.phone.store', SuccessAction::PROFILE, __('auth::mobile.profile.phoneSaved'));
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
        return $user !== null && AuthChannels::phone();
    }
}
