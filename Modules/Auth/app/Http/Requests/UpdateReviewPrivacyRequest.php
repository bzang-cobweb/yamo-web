<?php

namespace Modules\Auth\Http\Requests;

use Bza\Form\Http\Requests\FormBackedRequest;
use Modules\Auth\Forms\ReviewPrivacyForm;
use Modules\Auth\Models\User;

/**
 * Lieu sous mes avis. Règles et libellés : ceux du formulaire `ReviewPrivacyForm`.
 */
class UpdateReviewPrivacyRequest extends FormBackedRequest
{
    /**
     * Même capacité que la garde de la route (`can:updateMe`).
     */
    public function authorize(): bool
    {
        return $this->user()?->can('updateMe', User::class) ?? false;
    }

    protected function form(): ReviewPrivacyForm
    {
        return new ReviewPrivacyForm($this->user());
    }
}
