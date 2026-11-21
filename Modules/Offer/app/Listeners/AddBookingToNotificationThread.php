<?php

namespace Modules\Offer\Listeners;

use Illuminate\Support\Str;
use Modules\Notification\Events\NotificationThreadSerializing;
use Modules\Offer\Enums\BookingStatusEnum;
use Modules\Offer\Forms\BookingCancelForm;
use Modules\Offer\Forms\BookingEditForm;
use Modules\Offer\Http\Resources\BookingResource;
use Modules\Offer\Models\Booking;
use Modules\Offer\Support\BookingCache;

/**
 * Sur la page d'une notification du fil d'une demande (`booking:12`), la demande en
 * léger, sous `booking` (décision 2026-11-20-notification-generique). Une demande
 * supprimée : `booking` vaut `null`. Pour le membre qui l'a faite, tant qu'elle est en
 * attente, le bouton du bas ouvre « Modifier la demande » au lieu de « Mes réservations »
 * (décision 2026-11-21-detail-reservation-modifier) ; tant qu'il peut l'annuler, un second bouton,
 * « Annuler », ouvre son formulaire d'annulation (décision 2026-11-21-annuler-depuis-le-detail).
 */
class AddBookingToNotificationThread
{
    public function handle(NotificationThreadSerializing $event): void
    {
        if (! str_starts_with($event->thread, Booking::THREAD_PREFIX)) {
            return;
        }

        $id = Str::after($event->thread, Booking::THREAD_PREFIX);
        $booking = ctype_digit($id) ? app(BookingCache::class)->find((int) $id) : null;

        $event->data['booking'] = $booking === null ? null : (new BookingResource($booking))->resolve(request());

        $mine = $booking !== null && (string) $booking->user_id === (string) request()->user()?->getAuthIdentifier();

        if ($mine && $booking->isCancellable()) {
            $event->data['secondary_link'] = '/profile/'.BookingCancelForm::name().'?record='.$booking->getKey();
            $event->data['secondary_link_label'] = __('offer::booking.cancel.button');
        }

        if ($mine && $booking->status === BookingStatusEnum::PENDING) {
            $event->data['link'] = '/profile/'.BookingEditForm::name().'?record='.$booking->getKey();
            $event->data['link_label'] = __('offer::booking.form.edit');
        }
    }
}
