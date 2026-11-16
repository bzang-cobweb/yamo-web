<?php

namespace Modules\Auth\Http\Requests;

use Illuminate\Validation\Rule;
use Modules\Auth\Forms\EmailChangeForm;
use Modules\Auth\Models\User;
use Modules\Form\Http\Requests\FormBackedRequest;

/**
 * Ajouter ou changer son e-mail : règles du formulaire `auth.profile.email`, et une
 * adresse que personne d'autre n'a.
 */
class EmailChangeRequest extends FormBackedRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('updateMe', User::class) ?? false;
    }

    protected function form(): EmailChangeForm
    {
        return new EmailChangeForm($this->user());
    }

    public function rules(): array
    {
        $rules = parent::rules();
        $rules['email'][] = Rule::unique(User::class, 'email')->ignore($this->user()?->getKey());

        return $rules;
    }
}
