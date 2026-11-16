<?php

namespace Modules\Auth\Http\Requests;

use Modules\Auth\Forms\PasswordForm;
use Modules\Auth\Models\User;
use Modules\Form\Http\Requests\FormBackedRequest;

/**
 * Changer son mot de passe. Règles et libellés : ceux du formulaire `PasswordForm`.
 */
class UpdatePasswordRequest extends FormBackedRequest
{
    /**
     * Même capacité que la garde de la route (`can:updateMe`).
     */
    public function authorize(): bool
    {
        return $this->user()?->can('updateMe', User::class) ?? false;
    }

    protected function form(): PasswordForm
    {
        return new PasswordForm($this->user());
    }
}
