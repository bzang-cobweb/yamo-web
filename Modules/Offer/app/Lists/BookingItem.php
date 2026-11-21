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
     * et les dates, sans le nombre de personnes ; en gras, le prix et son unité (décisions 2026-11-21-reservations-vrai-titre,
     * 2026-11-21-reservations-type-de-location-titre, 2026-11-21-reservations-sans-nombre-personnes
     * et 2026-11-21-reservations-titres-inverses-sans-prix et 2026-11-21-reservations-prix-comme-accueil).
     */
    public static function make(Booking $booking, string $locale, bool $withGuest = false, bool $mine = false): ListItem
    {
        $offer = $booking->offer;
        $built = app(DomainRegistry::class)->require()->itemTitle($offer) ?? (string) $offer->getTranslation('title', $locale);
        $subtitle = implode(' · ', array_filter([
            $withGuest ? (string) $booking->user->full_name : null,
            $booking->type->label(),
            $booking->period($locale),
            $mine || $booking->guests === null ? null : __('offer::booking.list.guests', ['count' => $booking->guests], $locale),
        ]));

        if (! $mine) {
            return ListItem::make($booking->getKey(), $built, $subtitle)
                ->image(MediaUrl::onRequestHost($offer->photoUrl()))
                // Le statut en pastille de couleur ; en gras, le total.
                ->tag($booking->status->label(), $booking->status->tone())
                ->highlight($booking->formattedTotal($locale));
        }

        $domain = app(DomainRegistry::class)->require();
        // Le titre et le sous-titre comme toute carte d'offre ; puis, sur sa ligne, le type de demande et les dates
        // (décision 2026-11-22-reservations-ligne-dates).
        [$title, $line] = OfferCardTitle::composedFirst($offer, $locale);

        return ListItem::make($booking->getKey(), $title, $line)
            ->detail($subtitle)
            ->image(MediaUrl::onRequestHost($offer->photoUrl()))
            ->tag($booking->status->label(), $booking->status->tone())
            // La note des avis à droite, comme à l'accueil (décision 2026-11-21-reservations-note).
            ->rating($offer->reviews_avg_rating, (int) $offer->reviews_count)
            // En gras, comme à l'accueil : les détails puis le prix et son unité (« 45 000 FCFA / jour »,
            // décision 2026-11-21-reservations-prix-comme-accueil).
            ->highlight(implode(' · ', array_filter([...$domain->itemDetails($offer), $domain->formattedPrice($offer, $locale)])) ?: null);
    }
}
