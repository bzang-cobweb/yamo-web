<?php

namespace Modules\Offer\Support;

use Modules\Offer\Lists\OfferCardTitle;
use Modules\Offer\Models\Offer;

/**
 * L'en-tête de la page d'une notification dont le fil parle d'une offre (décision 2026-11-26-booking-summary) : une
 * demande (`booking:12`) ou une discussion avec l'éditeur (`offer_message:12:37`). Le titre saisi de l'offre, en lien
 * vers sa fiche, et dessous son titre composé (« Location meublée · Studio · Douala »), par la règle commune des
 * titres d'offre (`OfferCardTitle`).
 */
final class OfferHeading
{
    /**
     * L'en-tête de `$offer` dans la langue `$locale` ; `null` sans offre ou sans titre saisi.
     *
     * @return array{title: string, subtitle: string|null, link: string}|null
     */
    public static function of(?Offer $offer, string $locale): ?array
    {
        if ($offer === null) {
            return null;
        }

        $title = (string) $offer->getTranslation('title', $locale);
        if ($title === '') {
            return null;
        }

        return [
            'title' => $title,
            'subtitle' => OfferCardTitle::of($offer, $locale)[1],
            'link' => '/offers/'.$offer->getKey(),
        ];
    }
}
