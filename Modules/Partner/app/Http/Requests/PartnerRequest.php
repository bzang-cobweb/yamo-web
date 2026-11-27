<?php

namespace Modules\Partner\Http\Requests;

use Bza\Form\Http\Requests\FormBackedRequest;
use Bza\Form\Support\Form;
use Modules\Partner\Forms\PartnerForm;
use Modules\Partner\Models\Partner;

/**
 * Un partenaire envoyé depuis l'app : les règles du formulaire `PartnerForm` (décision
 * 2026-11-24-partenaires-par-les-membres).
 */
class PartnerRequest extends FormBackedRequest
{
    protected function form(): Form
    {
        $form = new PartnerForm($this->user());
        $partner = $this->route('partner');

        return $partner instanceof Partner ? $form->forRecord((string) $partner->getKey()) : $form;
    }
}
