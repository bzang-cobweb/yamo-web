<?php

namespace Modules\Auth\Http\Requests;

use Bza\Form\Http\Requests\FormBackedRequest;
use Modules\Auth\Forms\ResidenceForm;
use Modules\Auth\Models\User;

/**
 * Enregistrer son adresse de résidence. Règles et libellés : ceux du formulaire `ResidenceForm`.
 */
class UpdateResidenceRequest extends FormBackedRequest
{
    /**
     * Même capacité que la garde de la route (`can:updateMe`).
     */
    public function authorize(): bool
    {
        return $this->user()?->can('updateMe', User::class) ?? false;
    }

    protected function form(): ResidenceForm
    {
        return new ResidenceForm($this->user());
    }
}
