<?php

namespace Bza\Form\Tests\Fixtures;

use Bza\Form\Http\Requests\FormBackedRequest;
use Bza\Form\Support\Form;

/**
 * The request receiving the contact form: its rules are the form's.
 */
class ContactRequest extends FormBackedRequest
{
    protected function form(): Form
    {
        return new ContactForm($this->user());
    }
}
