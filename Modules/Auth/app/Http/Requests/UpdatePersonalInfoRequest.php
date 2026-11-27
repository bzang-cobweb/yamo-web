<?php

namespace Modules\Auth\Http\Requests;

use Bza\Form\Http\Requests\FormBackedRequest;
use Modules\Auth\Forms\PersonalInfoForm;
use Modules\Auth\Models\User;

/**
 * Modifier ses informations personnelles (prénom, nom). Règles et libellés : ceux du formulaire `PersonalInfoForm`.
 */
class UpdatePersonalInfoRequest extends FormBackedRequest
{
    /**
     * Même capacité que la garde de la route (`can:updateMe`).
     */
    public function authorize(): bool
    {
        return $this->user()?->can('updateMe', User::class) ?? false;
    }

    protected function form(): PersonalInfoForm
    {
        return new PersonalInfoForm($this->user());
    }
}
