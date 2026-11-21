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
     * `$mine` : la carte de « Mes réservations » — le titre saisi de l'offre en titre ; en
     * dessous, le titre de carte du domaine (« Location meublée · Studio · Douala »), puis le type de demande
     * et les dates, sans le nombre de personnes ni le total (décisions 2026-11-21-reservations-vrai-titre,
     * 2026-11-21-reservations-type-de-location-titre, 2026-11-21-reservations-sans-nombre-personnes
     * et 2026-11-21-reservations-titres-inverses-sans-prix).
     */
    public static function make(Booking $booking, string $locale, bool $withGuest = false, bool $mine = false): ListItem
    {
        $offer = $booking->offer;
        $domain = app(DomainRegistry::class)->require();
        $typed = (string) $offer->getTranslation('title', $locale);
        $built = $domain->itemTitle($offer);
        $subtitle = implode(' · ', array_filter([
            $withGuest ? (string) $booking->user->full_name : null,
            $booking->type->label(),
            $booking->period($locale),
            $mine || $booking->guests === null ? null : __('offer::booking.list.guests', ['count' => $booking->guests], $locale),
        ]));

        if (! $mine) {
            return ListItem::make($booking->getKey(), $built ?? $typed, $subtitle)
                ->image(MediaUrl::onRequestHost($offer->photoUrl()))
                // Le statut en pastille de couleur ; en gras, le total.
                ->tag($booking->status->label(), $booking->status->tone())
                ->highlight($booking->formattedTotal($locale));
        }

        $card = $domain->bookingItemTitle($offer, $locale) ?? $built;
        $title = $typed !== '' ? $typed : ($card ?? '');
        if ($card !== null && $card !== $title) {
            $subtitle = $card."\n".$subtitle;
        }

        return ListItem::make($booking->getKey(), $title, $subtitle)
            ->image(MediaUrl::onRequestHost($offer->photoUrl()))
            ->tag($booking->status->label(), $booking->status->tone());
    }
}
