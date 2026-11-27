<?php

namespace Modules\Offer\Listeners;

use Illuminate\Support\Str;
use Modules\Notification\Events\NotificationThreadSerializing;
use Modules\Offer\Models\Offer;
use Modules\Offer\Notifications\OfferNotificationFilter;
use Modules\Offer\Support\OfferHeading;

/**
 * Sur la page d'une notification d'une discussion avec l'éditeur (`offer_message:12:37`, décision
 * 2026-11-26-messages-editeur), l'en-tête de l'offre, comme pour une demande (décision 2026-11-26-booking-summary) :
 * son titre vers sa fiche et son titre composé. Une offre supprimée garde son en-tête ; une offre introuvable, aucun.
 */
final class AddOfferToMessageThread
{
    public function handle(NotificationThreadSerializing $event): void
    {
        $prefix = OfferNotificationFilter::messageThreadPrefix();
        if (! str_starts_with($event->thread, $prefix)) {
            return;
        }

        $id = Str::before(Str::after($event->thread, $prefix), ':');
        $offer = ctype_digit($id) ? Offer::query()->withTrashed()->find((int) $id) : null;

        $heading = OfferHeading::of($offer, app()->getLocale());
        if ($heading !== null) {
            $event->data['heading'] = $heading;
        }

        // Le libellé du bouton, lu à l'affichage dans la langue du lecteur : « Écrire », messages déjà reçus compris
        // (décision 2026-11-27-bouton-ecrire).
        if (($event->data['link'] ?? null) !== null) {
            $event->data['link_label'] = (string) __('offer::message.notify.reply');
        }
    }
}
