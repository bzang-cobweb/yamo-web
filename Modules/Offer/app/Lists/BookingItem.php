<?php

namespace Modules\Offer\Lists;

use App\Support\MediaUrl;
use Modules\Form\Support\ListItem;
use Modules\Offer\Domains\DomainRegistry;
use Modules\Offer\Models\Booking;

/**
 * Une demande dans une liste (« Mes réservations », « Réservations reçues ») : la
 * photo et le titre de l'offre ; en dessous, le membre (pour le propriétaire), les
 * dates et les voyageurs ; en gras, le statut et le total.
 */
final class BookingItem
{
    public static function make(Booking $booking, string $locale, bool $withGuest = false): ListItem
    {
        $offer = $booking->offer;
        $title = app(DomainRegistry::class)->require()->itemTitle($offer) ?? (string) $offer->getTranslation('title', $locale);
        $subtitle = implode(' · ', array_filter([
            $withGuest ? (string) $booking->user->full_name : null,
            $booking->type->label(),
            $booking->period($locale),
            $booking->guests === null ? null : __('offer::booking.list.guests', ['count' => $booking->guests], $locale),
        ]));

        return ListItem::make($booking->getKey(), $title, $subtitle)
            ->image(MediaUrl::onRequestHost($offer->photoUrl()))
            ->highlight(implode(' · ', array_filter([$booking->status->label(), $booking->formattedTotal($locale)])));
    }
}
