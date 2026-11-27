<?php

namespace Bza\Form\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Bza\Form\Support\ContactDetails;

/**
 * No published text gives a way to reach its author outside the application
 * (decision 2026-11-19-contrat-annonces-sans-coordonnees): phone, e-mail,
 * link, social network, call to action, pseudonym. See `ContactDetails`.
 */
final class NoContactDetails implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (is_string($value) && ContactDetails::find($value) !== null) {
            $fail('form::contact.message')->translate();
        }
    }
}
