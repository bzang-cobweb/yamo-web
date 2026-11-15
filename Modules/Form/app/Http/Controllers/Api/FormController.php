<?php

namespace Modules\Form\Http\Controllers\Api;

use Modules\Form\Http\Resources\FormResource;
use Modules\Form\Support\Form;

/**
 * Définitions des formulaires affichés par l'app mobile.
 */
class FormController
{
    /**
     * Définition d'un formulaire : champs (libellés traduits), options, valeurs
     * par défaut, bouton d'envoi. Formulaire inconnu : 404 ; formulaire réservé
     * demandé sans jeton : 401.
     *
     * @unauthenticated
     */
    public function show(Form $form): FormResource
    {
        return new FormResource($form);
    }
}
