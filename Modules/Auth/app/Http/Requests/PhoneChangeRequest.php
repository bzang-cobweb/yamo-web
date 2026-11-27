<?php

namespace Modules\Auth\Http\Requests;

use Bza\Form\Http\Requests\FormBackedRequest;
use Modules\Auth\Forms\PhoneChangeForm;
use Modules\Auth\Models\User;
use Modules\Auth\Rules\UniquePhone;
use Modules\Auth\Support\AuthChannels;

/**
 * Ajouter ou changer son téléphone : règles du formulaire `auth.profile.phone`
 * (numéro valide pour le pays choisi), et un numéro que personne d'autre n'a.
 */
class PhoneChangeRequest extends FormBackedRequest
{
    /**
     * Même capacité que la garde de la route, et canal actif dans l'application.
     */
    public function authorize(): bool
    {
        return AuthChannels::phone() && ($this->user()?->can('updateMe', User::class) ?? false);
    }

    protected function form(): PhoneChangeForm
    {
        return new PhoneChangeForm($this->user());
    }

    public function rules(): array
    {
        $rules = parent::rules();
        $rules['phone'][] = new UniquePhone($this->user()?->getKey());

        return $rules;
    }
}
