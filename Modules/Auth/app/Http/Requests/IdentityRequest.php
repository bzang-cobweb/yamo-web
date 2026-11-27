<?php

namespace Modules\Auth\Http\Requests;

use Bza\Form\Http\Requests\FormBackedRequest;
use Bza\Form\Support\Form;
use Modules\Auth\Forms\IdentityForm;

/** Les documents d'identité envoyés, vérifiés par le formulaire (décision 2026-11-25-identification). */
class IdentityRequest extends FormBackedRequest
{
    protected function form(): Form
    {
        return new IdentityForm($this->user());
    }
}
