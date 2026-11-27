<?php

namespace Modules\Auth\Http\Requests;

use Bza\Form\Http\Requests\FormBackedRequest;
use Modules\Auth\Forms\ProfileVisibilityForm;
use Modules\Auth\Models\User;

/**
 * Visibilité du profil. Règles et libellés : ceux du formulaire `ProfileVisibilityForm`.
 */
class UpdateProfileVisibilityRequest extends FormBackedRequest
{
    /**
     * Même capacité que la garde de la route (`can:updateMe`).
     */
    public function authorize(): bool
    {
        return $this->user()?->can('updateMe', User::class) ?? false;
    }

    protected function form(): ProfileVisibilityForm
    {
        return new ProfileVisibilityForm($this->user());
    }
}
