<?php

namespace Modules\Auth\Http\Requests;

use Modules\Auth\Forms\PreferencesForm;
use Modules\Auth\Models\User;
use Modules\Form\Http\Requests\FormBackedRequest;

/**
 * Modifier ses préférences (langue). Règles et libellés : ceux du formulaire `PreferencesForm`.
 */
class UpdatePreferencesRequest extends FormBackedRequest
{
    /**
     * Même capacité que la garde de la route (`can:updateMe`).
     */
    public function authorize(): bool
    {
        return $this->user()?->can('updateMe', User::class) ?? false;
    }

    protected function form(): PreferencesForm
    {
        return new PreferencesForm($this->user());
    }
}
