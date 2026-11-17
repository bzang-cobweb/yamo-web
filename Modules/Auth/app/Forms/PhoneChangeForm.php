<?php

namespace Modules\Auth\Forms;

use Illuminate\Contracts\Auth\Authenticatable;
use Modules\Auth\Models\User;
use Modules\Auth\Support\AuthChannels;
use Modules\Auth\Support\PhoneNumbers;
use Modules\Form\Support\Field;
use Modules\Form\Support\Form;

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
        $defaultCountry = $user?->phoneCountry?->code ?? $user?->country?->code;

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
