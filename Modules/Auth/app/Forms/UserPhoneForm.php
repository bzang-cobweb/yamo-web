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
 * Ajouter un numéro supplémentaire (décision 2026-11-27-plusieurs-numeros), ouvert depuis « Mes numéros » : pays et
 * numéro ; un code part par SMS à ce numéro, qui n'est ajouté, vérifié, qu'une fois le code validé. Retour à la liste.
 */
class UserPhoneForm extends Form
{
    public static function name(): string
    {
        return 'auth.profile.phones.create';
    }

    public function fields(): array
    {
        $user = $this->user instanceof User ? $this->user : null;
        // Le pays courant d'office (décision 2026-11-18-espace-pays).
        $defaultCountry = CurrentCountry::get()?->code ?? $user?->phoneCountry?->code ?? $user?->country?->code;

        return [
            Field::phone('phone')
                ->label(__('auth::phones.form.phone'))
                ->help(__('auth::phones.form.help'))
                ->options(PhoneNumbers::countryOptions())
                ->default(['country' => $defaultCountry, 'number' => null])
                ->required(),
        ];
    }

    public function title(): string
    {
        return __('auth::phones.form.title');
    }

    public function submission(): Submission
    {
        return Submission::toRoute('api.v1.auth.me.phones.store', SuccessAction::BACK, __('auth::phones.form.saved'));
    }

    public function submitLabel(): string
    {
        return __('auth::form.code.send');
    }

    /**
     * Un compte connecté, si le canal téléphone est actif dans l'application.
     */
    public function canBeViewedBy(?Authenticatable $user): bool
    {
        return $user !== null && AuthChannels::phone();
    }
}
