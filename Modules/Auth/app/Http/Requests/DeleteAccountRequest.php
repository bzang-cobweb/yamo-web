<?php

namespace Modules\Auth\Http\Requests;

use Bza\Form\Http\Requests\FormBackedRequest;
use Modules\Auth\Forms\DeleteAccountForm;
use Modules\Auth\Models\User;

/**
 * Supprimer son compte. Règles et libellés : ceux du formulaire `DeleteAccountForm`.
 */
class DeleteAccountRequest extends FormBackedRequest
{
    /**
     * Même capacité que la garde de la route (`can:deleteMe`).
     */
    public function authorize(): bool
    {
        return $this->user()?->can('deleteMe', User::class) ?? false;
    }

    protected function form(): DeleteAccountForm
    {
        return new DeleteAccountForm($this->user());
    }
}
