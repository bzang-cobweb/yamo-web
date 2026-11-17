<?php

namespace Modules\Form\Http\Controllers\Api;

use Illuminate\Http\Request;
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
     * demandé sans jeton : 401. `record` lie le formulaire à un enregistrement
     * (une adresse) : prérempli et envoyé pour le modifier ; 404 s'il n'est pas
     * au compte. Un formulaire qui n'en a pas l'usage l'ignore.
     *
     * @unauthenticated
     */
    public function show(Request $request, Form $form): FormResource
    {
        $request->validate([
            /** Identifiant de l'enregistrement à modifier. @example 12 */
            'record' => ['sometimes', 'string', 'max:64'],
        ]);

        $record = $request->query('record');

        return new FormResource(is_string($record) && $record !== '' ? $form->forRecord($record) : $form);
    }
}
