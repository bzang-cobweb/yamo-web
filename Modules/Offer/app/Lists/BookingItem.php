<?php

namespace Modules\Offer\Lists;

use App\Support\MediaUrl;
use Modules\Form\Support\ListItem;
use Modules\Offer\Domains\DomainRegistry;
use Modules\Offer\Models\Booking;

/**
 * Une demande dans une liste (« Mes réservations », « Réservations reçues ») : la
 * photo et le titre de l'offre ; en dessous, le membre (pour le propriétaire), les
 * dates et les voyageurs ; le statut en pastille de couleur ; en gras, le total.
 */
final class BookingItem
{
    /**
     * `$withTypedTitle` : le titre saisi de l'offre sur la première ligne sous le titre, s'il en
     * diffère (« Mes réservations », décision 2026-11-21-reservations-vrai-titre). `$withRentalType` :
     * le titre de carte du domaine (« Location meublée · Studio · Douala », décision
     * 2026-11-21-reservations-type-de-location-titre).
     */
    public static function make(Booking $booking, string $locale, bool $withGuest = false, bool $withTypedTitle = false, bool $withRentalType = false): ListItem
    {
        $offer = $booking->offer;
        $typed = (string) $offer->getTranslation('title', $locale);
        $title = app(DomainRegistry::class)->require()->itemTitle($offer) ?? $typed;
        $subtitle = implode(' · ', array_filter([
            $withGuest ? (string) $booking->user->full_name : null,
            $booking->type->label(),
            $booking->period($locale),
            $booking->guests === null ? null : __('offer::booking.list.guests', ['count' => $booking->guests], $locale),
        ]));

        if ($withTypedTitle && $typed !== '' && $typed !== $title) {
            $subtitle = $typed."\n".$subtitle;
        }

        if ($withRentalType) {
            $title = app(DomainRegistry::class)->require()->bookingItemTitle($offer, $locale) ?? $title;
        }

        return ListItem::make($booking->getKey(), $title, $subtitle)
            ->image(MediaUrl::onRequestHost($offer->photoUrl()))
            // Le statut en pastille de couleur ; en gras, le total.
            ->tag($booking->status->label(), $booking->status->tone())
            ->highlight($booking->formattedTotal($locale));
    }
}
