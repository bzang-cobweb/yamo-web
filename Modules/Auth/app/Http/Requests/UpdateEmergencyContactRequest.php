<?php

namespace Modules\Auth\Http\Requests;

use Bza\Form\Http\Requests\FormBackedRequest;
use Modules\Auth\Forms\EmergencyContactForm;
use Modules\Auth\Models\User;

/**
 * Enregistrer son contact d'urgence. Règles et libellés : ceux du formulaire `EmergencyContactForm`.
 */
class UpdateEmergencyContactRequest extends FormBackedRequest
{
    /**
     * Même capacité que la garde de la route (`can:updateMe`).
     */
    public function authorize(): bool
    {
        return $this->user()?->can('updateMe', User::class) ?? false;
    }

    protected function form(): EmergencyContactForm
    {
        return new EmergencyContactForm($this->user());
    }
}
