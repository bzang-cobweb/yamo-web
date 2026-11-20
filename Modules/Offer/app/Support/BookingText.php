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
            'period' => $booking->period($locale),
            'type' => __('offer::booking.notify.type_lower.'.$booking->type->value),
            // « Séjour », « Visite » : le début du texte d'une demande (décision 2026-11-20-texte-sans-verbe).
            'kind' => __('offer::booking.types.'.$booking->type->value),
        ];
    }

    /**
     * Le texte d'une carte, sans le nom de qui agit, pour l'étape `$step` (`requested`,
     * `accepted`…) : « Séjour pour « Studio photos ». », « Visite pour « Appartement ». ».
     * Les dates, la durée, les personnes et le prix s'affichent à part (décisions
     * 2026-11-20-personnes-a-part et 2026-11-20-heures-arrivee-depart).
     */
    public static function summary(Booking $booking, string $step): string
    {
        return __("offer::booking.notify.{$step}.summary", self::replacements($booking));
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
