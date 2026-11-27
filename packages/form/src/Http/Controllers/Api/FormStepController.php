<?php

namespace Bza\Form\Http\Controllers\Api;

use Dedoc\Scramble\Attributes\QueryParameter;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;
use Bza\Form\Support\Form;

/**
 * Validation of one step of a multi-step form, before moving on to the next.
 */
class FormStepController
{
    /**
     * Validate step `{step}` (starting at 1): only its fields are checked, with
     * the form's rules. Nothing is saved. 204 if the step is valid,
     * 422 with the errors otherwise, 404 if the step does not exist. The final submission is made
     * through the form's own call, which validates everything. `record` (as for the
     * definition): the steps of a form bound to a record may
     * differ (the « Langue » step of an edited offer, decision
     * 2026-11-19-contrat-langue-saisie-allemand).
     *
     * @unauthenticated
     */
    #[QueryParameter('record', description: "Identifiant de l'enregistrement modifié.", type: 'string', required: false, example: '12')]
    public function validate(Request $request, Form $form, int $step): Response
    {
        // In the URL, not in the step values; an invalid value is ignored.
        $record = $request->query('record');
        $form = is_string($record) && $record !== '' && strlen($record) <= 64 ? $form->forRecord($record) : $form;

        $rules = $form->stepRules($step);
        abort_if($rules === null, 404);

        Validator::make($request->all(), $rules, [], $form->attributes())->validate();

        return response()->noContent();
    }
}
