<?php

namespace Modules\Offer\Lists;

use App\Support\MediaUrl;
use Modules\Form\Support\ListItem;
use Modules\Offer\Models\Booking;

/**
 * Une carte de « Demandes » d'une offre (décision 2026-11-21-demandes-d-une-offre), à elle, sans reprendre
 * celle de « Mes réservations » : le logement est déjà nommé sous les filtres, la carte ne le répète pas.
 * En titre, le type de demande et les dates (« Visite · le 23 nov. 2026 ») ; la photo du logement, le statut
 * dessus ; en gras, le total d'un séjour.
 */
final class OfferRequestItem
{
    public static function make(Booking $booking, string $locale): ListItem
    {
        $title = implode(' · ', array_filter([$booking->type->label(), $booking->period($locale)]));

        return ListItem::make($booking->getKey(), $title)
            ->image(MediaUrl::onRequestHost($booking->offer->photoUrl()))
            ->tag($booking->status->label(), $booking->status->tone(), onImage: true)
            ->highlight($booking->formattedTotal($locale));
    }
}
