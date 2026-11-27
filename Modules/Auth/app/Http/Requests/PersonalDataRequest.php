<?php

namespace Modules\Auth\Http\Requests;

use Bza\Form\Http\Requests\FormBackedRequest;
use Modules\Auth\Forms\PersonalDataForm;
use Modules\Auth\Models\User;

/**
 * Demander ses données. Rien à saisir (formulaire `PersonalDataForm`).
 */
class PersonalDataRequest extends FormBackedRequest
{
    /**
     * Même capacité que la garde de la route (`can:updateMe`).
     */
    public function authorize(): bool
    {
        return $this->user()?->can('updateMe', User::class) ?? false;
    }

    protected function form(): PersonalDataForm
    {
        return new PersonalDataForm($this->user());
    }
}
