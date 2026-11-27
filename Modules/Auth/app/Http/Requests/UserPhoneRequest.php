<?php

namespace Modules\Auth\Http\Requests;

use Bza\Form\Http\Requests\FormBackedRequest;
use Modules\Auth\Forms\UserPhoneForm;
use Modules\Auth\Models\User;
use Modules\Auth\Rules\AvailableExtraPhone;
use Modules\Auth\Support\AuthChannels;

/**
 * Ajouter un numéro supplémentaire : règles du formulaire `auth.profile.phones.create` (numéro valide pour le pays
 * choisi), et un numéro qui n'est ni déjà au compte, ni à un autre (décision 2026-11-27-plusieurs-numeros).
 */
class UserPhoneRequest extends FormBackedRequest
{
    /**
     * Même capacité que la garde de la route, et canal actif dans l'application.
     */
    public function authorize(): bool
    {
        return AuthChannels::phone() && ($this->user()?->can('updateMe', User::class) ?? false);
    }

    protected function form(): UserPhoneForm
    {
        return new UserPhoneForm($this->user());
    }

    public function rules(): array
    {
        $rules = parent::rules();
        $user = $this->user();

        if ($user instanceof User) {
            $rules['phone'][] = new AvailableExtraPhone($user);
        }

        return $rules;
    }
}
