<?php

namespace Modules\Auth\Http\Requests;

use Modules\Auth\Forms\IdentityForm;
use Modules\Form\Http\Requests\FormBackedRequest;
use Modules\Form\Support\Form;

/** Les documents d'identité envoyés, vérifiés par le formulaire (décision 2026-11-25-identification). */
class IdentityRequest extends FormBackedRequest
{
    protected function form(): Form
    {
        return new IdentityForm($this->user());
    }
}
