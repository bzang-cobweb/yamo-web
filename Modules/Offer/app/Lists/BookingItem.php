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
     * `$mine` : la carte de « Mes réservations » — le titre saisi de l'offre et la ville en titre ; en
     * dessous, le titre de carte du domaine (« Location meublée · Studio »), puis le type de demande
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
        // Sous le titre : le type, puis les dates.
        $subtitle = implode("\n", array_filter([
            $card !== null && $card !== $title ? $card : null,
            $subtitle,
        ], fn (?string $line): bool => $line !== null && $line !== ''));
        // La ville au bout du titre : « Studio meublé à Bonanjo · Douala » (décision
        // 2026-11-21-reservations-ville-sur-le-titre).
        $title = implode(' · ', array_filter([$title, $offer->address?->city], fn (?string $part): bool => $part !== null && $part !== ''));

        return ListItem::make($booking->getKey(), $title, $subtitle)
            ->image(MediaUrl::onRequestHost($offer->photoUrl()))
            ->tag($booking->status->label(), $booking->status->tone());
    }
}
