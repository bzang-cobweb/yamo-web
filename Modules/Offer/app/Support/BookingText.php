<?php

namespace Modules\Offer\Support;

use Modules\Offer\Domains\DomainRegistry;
use Modules\Offer\Models\Booking;

/**
 * Les textes d'une demande, dans la langue courante (décision 2026-11-20-texte-demande) :
 * les remplacements des traductions et le texte d'une carte, sans le nom de qui
 * agit. Les mêmes dans une notification et dans le calendrier d'une offre.
 */
final class BookingText
{
    /**
     * @return array<string, string>
     */
    public static function replacements(Booking $booking): array
    {
        $locale = app()->getLocale();

        return [
            'app' => (string) config('app.name'),
            'guest' => (string) $booking->user->full_name,
            // Le titre saisi par le propriétaire, pas le titre construit de la liste.
            'offer' => (string) $booking->offer->getTranslation('title', $locale),
            'period' => $booking->period($locale),
            'type' => __('offer::booking.notify.type_lower.'.$booking->type->value),
            // « Séjour », « Visite » : le début du texte d'une demande (décision 2026-11-20-texte-sans-verbe).
            'kind' => __('offer::booking.types.'.$booking->type->value),
        ];
    }

    /**
     * Le texte d'une carte, sans le nom de qui agit, pour l'étape `$step`
     * (`requested`, `accepted`…) :
     * - un séjour (logement meublé) : sa durée après les dates, le prix par jour et le
     *   nombre de voyageurs (« … (3 jours) pour « Studio photos », 85 000 FCFA / jour (2 personnes). ») ;
     * - une visite : le loyer par mois (« … pour « Appartement », 250 000 FCFA / mois. »).
     *
     * Sans prix ou sans voyageurs, le texte s'en passe.
     */
    public static function summary(Booking $booking, string $step): string
    {
        $replace = self::replacements($booking);
        $key = "offer::booking.notify.{$step}.summary";
        $price = app(DomainRegistry::class)->require()->formattedPrice($booking->offer, app()->getLocale());
        $days = $booking->days();
        $people = $booking->guests === null ? null : trans_choice('offer::booking.notify.people', $booking->guests, ['count' => $booking->guests]);

        return match (true) {
            $days !== null && $price !== null && $people !== null && trans()->has($key.'_stay') => __($key.'_stay', [
                ...$replace,
                'days' => trans_choice('offer::booking.notify.days', $days, ['count' => $days]),
                'price' => $price,
                'people' => $people,
            ]),
            $people !== null && trans()->has($key.'_people') => __($key.'_people', [...$replace, 'people' => $people]),
            $days === null && $price !== null && trans()->has($key.'_visit') => __($key.'_visit', [...$replace, 'price' => $price]),
            default => __($key, $replace),
        };
    }

    /**
     * Le titre de l'offre comme lien vers l'annonce, s'il figure dans `$text`.
     *
     * @return list<array{text: string, link: string}>
     */
    public static function offerLink(Booking $booking, string $text): array
    {
        $title = self::replacements($booking)['offer'];

        return $title !== '' && str_contains($text, $title) ? [['text' => $title, 'link' => '/offers/'.$booking->offer_id]] : [];
    }
}
