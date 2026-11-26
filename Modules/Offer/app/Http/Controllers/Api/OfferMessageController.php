<?php

namespace Modules\Offer\Http\Controllers\Api;

use Illuminate\Http\Response;
use Modules\Auth\Models\User;
use Modules\Offer\Http\Requests\OfferMessageRequest;
use Modules\Offer\Models\Offer;
use Modules\Offer\Notifications\OfferMessageReceived;

/**
 * Écrire à l'éditeur d'une offre, ou lui répondre (décision 2026-11-26-messages-editeur) : chaque message est une
 * notification envoyée à l'autre partie, dans le fil `offer-message:{offre}:{membre}`. La réponse est vide : l'app
 * revient à l'écran précédent.
 */
class OfferMessageController
{
    /**
     * Envoyer un message : un membre à l'éditeur (sans `member`), l'éditeur à un membre qui lui a déjà écrit au sujet
     * de cette offre (`member`). Une offre invisible répond 404 ; s'écrire à soi-même, écrire le premier en éditeur ou
     * écrire dans la discussion d'un autre, 403.
     */
    public function store(OfferMessageRequest $request, Offer $offer): Response
    {
        /** @var User $sender */
        $sender = $request->user();
        /** @var array{message: string} $data */
        $data = $request->validated();

        // Le membre de la discussion est toujours celui qui n'est pas l'éditeur.
        $member = $request->member() ?? $sender;
        $publisher = $offer->user;
        $recipient = (int) $sender->getKey() === (int) $offer->user_id ? $member : $publisher;
        abort_if($recipient === null, 404);

        $recipient->notify(new OfferMessageReceived($offer, $sender, $member, trim($data['message'])));

        return response()->noContent();
    }
}
