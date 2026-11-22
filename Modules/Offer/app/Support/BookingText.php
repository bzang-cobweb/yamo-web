<?php

namespace Modules\Offer\Support;

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
            // En chiffres, dans l'ordre de la langue : « du 05/12/2026 au 07/12/2026 » (décision 2026-11-22-date-entiere-chiffres).
            'period' => $booking->period($locale, numeric: true),
            'type' => __('offer::booking.notify.type_lower.'.$booking->type->value),
            // « demande de séjour », « demande de visite » (décision 2026-11-22-annulation-visite-sejour).
            'request' => __('offer::booking.notify.request_lower.'.$booking->type->value),
            // « Séjour », « Visite » : le début du texte d'une demande (décision 2026-11-20-texte-sans-verbe).
            'kind' => __('offer::booking.types.'.$booking->type->value),
        ];
    }

    /**
     * Le texte d'une carte, sans le nom de qui agit, pour l'étape `$step` (`requested`,
     * `accepted`…) (décision 2026-11-20-texte-demande-location) : « Demande de location
     * « Studio » du 5 déc. 2026 au 7 déc. 2026 pour 2 personnes. » pour un séjour,
     * « Demande de visite « Maison » le 28 nov. 2026. » pour une visite.
     */
    public static function summary(Booking $booking, string $step): string
    {
        $replace = self::replacements($booking);
        $key = "offer::booking.notify.{$step}.summary";

        if ($booking->guests !== null && $booking->days() !== null && trans()->has($key.'_stay')) {
            return __($key.'_stay', [...$replace, 'people' => trans_choice('offer::booking.notify.people', $booking->guests, ['count' => $booking->guests])]);
        }

        return __($key, $replace);
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
