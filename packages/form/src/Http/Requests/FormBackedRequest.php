<?php

namespace Bza\Form\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Bza\Form\Support\Form;

/**
 * FormRequest of a form served to the app: rules and labels come from the
 * form itself — a single source for display and validation. Only
 * the form's fields are validated; any other field sent is ignored by
 * `validated()`.
 */
abstract class FormBackedRequest extends FormRequest
{
    /**
     * The form, built for the request's user (default values).
     */
    abstract protected function form(): Form;

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return $this->form()->rules();
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return $this->form()->attributes();
    }
}
