<?php

namespace Modules\Form\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;
use Modules\Form\Support\Form;

/**
 * Validation d'une étape d'un formulaire à étapes, avant de passer à la suivante.
 */
class FormStepController
{
    /**
     * Valider l'étape `{step}` (à partir de 1) : seuls ses champs sont vérifiés, avec
     * les règles du formulaire. Rien n'est enregistré. 204 si l'étape est valide,
     * 422 avec les erreurs sinon, 404 si l'étape n'existe pas. L'envoi final se fait
     * par l'appel propre au formulaire, qui valide tout. `record` (comme pour la
     * définition) : les étapes d'un formulaire lié à un enregistrement peuvent
     * différer (l'étape « Langue » d'une offre modifiée, décision
     * 2026-11-19-contrat-langue-saisie-allemand).
     *
     * @unauthenticated
     */
    public function validate(Request $request, Form $form, int $step): Response
    {
        $request->validate([
            /** Identifiant de l'enregistrement modifié. @example 12 */
            'record' => ['sometimes', 'string', 'max:64'],
        ]);
        $record = $request->query('record');
        $form = is_string($record) && $record !== '' ? $form->forRecord($record) : $form;

        $rules = $form->stepRules($step);
        abort_if($rules === null, 404);

        Validator::make($request->all(), $rules, [], $form->attributes())->validate();

        return response()->noContent();
    }
}
