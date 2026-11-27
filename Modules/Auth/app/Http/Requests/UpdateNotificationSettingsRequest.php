<?php

namespace Modules\Auth\Http\Requests;

use Bza\Form\Http\Requests\FormBackedRequest;
use Modules\Auth\Forms\NotificationSettingsForm;
use Modules\Auth\Models\User;

/**
 * Modifier ses choix de notifications. Règles et libellés : ceux du formulaire `NotificationSettingsForm`.
 */
class UpdateNotificationSettingsRequest extends FormBackedRequest
{
    /**
     * Même capacité que la garde de la route (`can:updateMe`).
     */
    public function authorize(): bool
    {
        return $this->user()?->can('updateMe', User::class) ?? false;
    }

    protected function form(): NotificationSettingsForm
    {
        return new NotificationSettingsForm($this->user());
    }
}
