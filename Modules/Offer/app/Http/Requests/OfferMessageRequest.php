<?php

namespace Modules\Offer\Http\Requests;

use Illuminate\Auth\Access\Response;
use Illuminate\Support\Facades\Gate;
use Modules\Auth\Models\User;
use Modules\Form\Http\Requests\FormBackedRequest;
use Modules\Offer\Forms\OfferMessageForm;
use Modules\Offer\Models\Offer;

/**
 * Un message au sujet d'une offre, validé par le formulaire `offer.message` (décision 2026-11-26-messages-editeur).
 * Les droits dépendent du membre à qui l'on répond (`member`) : ils sont vérifiés ici, par `OfferPolicy::message()`,
 * pas sur la route, qui ne le connaît pas.
 */
class OfferMessageRequest extends FormBackedRequest
{
    public function authorize(): Response
    {
        /** @var Offer $offer */
        $offer = $this->route('offer');

        return Gate::forUser($this->user())->inspect('message', [$offer, $this->member()]);
    }

    /**
     * Le membre à qui répond l'éditeur, ou `null` : un membre écrit à l'éditeur. Un membre inconnu répond 404.
     */
    public function member(): ?User
    {
        $key = $this->input('member');
        if ($key === null || $key === '') {
            return null;
        }

        abort_unless(is_scalar($key) && ctype_digit((string) $key), 404);

        return User::query()->whereKey((int) $key)->first() ?? abort(404);
    }

    protected function form(): OfferMessageForm
    {
        return new OfferMessageForm($this->user());
    }
}
