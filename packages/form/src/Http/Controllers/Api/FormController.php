<?php

namespace Bza\Form\Http\Controllers\Api;

use Illuminate\Http\Request;
use Bza\Form\Http\Resources\FormResource;
use Bza\Form\Support\Form;

/**
 * Definitions of the forms displayed by the mobile app.
 */
class FormController
{
    /**
     * Definition of a form: fields (translated labels), options, default
     * values, submit button. Unknown form: 404; restricted form
     * requested without a token: 401. `record` binds the form to a record
     * (an address): prefilled and sent to update it; 404 if it does not belong
     * to the account. A form that has no use for it ignores it.
     *
     * @unauthenticated
     */
    public function show(Request $request, Form $form): FormResource
    {
        $request->validate([
            /** Identifier of the record to update. @example 12 */
            'record' => ['sometimes', 'string', 'max:64'],
        ]);

        $record = $request->query('record');

        return new FormResource(is_string($record) && $record !== '' ? $form->forRecord($record) : $form);
    }
}
