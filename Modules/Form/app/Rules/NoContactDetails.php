<?php

namespace Modules\Form\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Modules\Form\Support\ContactDetails;

/**
 * Aucun texte publié ne donne de quoi joindre son auteur hors de l'application
 * (décision 2026-11-19-contrat-annonces-sans-coordonnees) : téléphone, e-mail,
 * lien, réseau social, formule d'appel, pseudonyme. Voir `ContactDetails`.
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
