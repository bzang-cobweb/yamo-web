<?php

namespace Modules\Partner\Http\Requests;

use Bza\Form\Http\Requests\FormBackedRequest;
use Modules\Partner\Forms\PartnerMessageForm;

/**
 * Un message dans le fil d'un partenaire, validé par le formulaire `partner.message` (décision
 * 2026-11-27-partenaire-messages) ; les droits sont vérifiés sur la route (`PartnerPolicy::message()`).
 */
class PartnerMessageRequest extends FormBackedRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function form(): PartnerMessageForm
    {
        return new PartnerMessageForm($this->user());
    }
}
