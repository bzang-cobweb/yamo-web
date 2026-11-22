<?php

namespace Modules\Offer\Lists;

use Modules\Form\Support\ListItem;
use Modules\Offer\Models\Booking;

/**
 * Une carte de « Demandes » d'une offre (décision 2026-11-21-demandes-d-une-offre), à elle, sans reprendre
 * celle de « Mes réservations » : le logement est déjà nommé sous les filtres, la carte ne le répète pas.
 * Le statut, puis en titre le nom du membre, en lien vers sa page (décisions 2026-11-21-demandes-nom-membre et
 * 2026-11-21-demandes-lien-membre) ; dessous, le type de
 * demande et les dates (« Visite · le 23 nov. 2026 ») ; sans total (décision 2026-11-22-demandes-sans-total). Sans photo : la même sur toutes les cartes (décision 2026-11-21-demandes-sans-photo).
 */
final class OfferRequestItem
{
    public static function make(Booking $booking, string $locale): ListItem
    {
        $when = implode(' · ', array_filter([$booking->type->label(), $booking->period($locale)]));
        $member = trim((string) $booking->user?->full_name);

        return ListItem::make($booking->getKey(), $member !== '' ? $member : $when, $member !== '' ? $when : null)
            ->titleLink($member !== '' && $booking->user !== null ? '/members/'.$booking->user->getKey() : null)
            ->tag(...$booking->statusTag());
    }
}
