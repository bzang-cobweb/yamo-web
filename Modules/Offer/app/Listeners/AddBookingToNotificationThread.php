<?php

namespace Modules\Offer\Listeners;

use Illuminate\Support\Str;
use Modules\Auth\Models\User;
use Modules\Auth\Support\PhoneNumbers;
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
    /**
     * « Hôte » ou « Membre » (avec le lien de sa page), « Téléphone », « E-mail » ; une ligne sans valeur est omise.
     *
     * @return list<array{key: string, label: string, value: string, link?: string}>
     */
    private function contactLines(?User $person, string $role): array
    {
        if ($person === null) {
            return [];
        }

        $line = fn (string $key, ?string $value, ?string $link = null): ?array => $value === null || $value === ''
            ? null
            : ['key' => $key, 'label' => __('offer::booking.lines.'.$key), 'value' => $value, ...($link === null ? [] : ['link' => $link])];

        return array_values(array_filter([
            $line($role, (string) $person->full_name, '/members/'.$person->getKey()),
            $line('phone', $person->phone === null ? null : PhoneNumbers::international($person->phone)),
            $line('email', $person->email),
        ]));
    }

    public function handle(NotificationThreadSerializing $event): void
    {
        if (! str_starts_with($event->thread, Booking::THREAD_PREFIX)) {
            return;
        }

        $id = Str::after($event->thread, Booking::THREAD_PREFIX);
        $booking = ctype_digit($id) ? app(BookingCache::class)->find((int) $id) : null;

        $event->data['booking'] = $booking === null ? null : (new BookingResource($booking))->resolve(request());

        $mine = $booking !== null && (string) $booking->user_id === (string) request()->user()?->getAuthIdentifier();

        // L'autre partie, après les lignes de la demande : pour le membre qui l'a faite, l'hôte ; pour l'hôte,
        // le membre ; son nom vers sa page, son téléphone et son e-mail (décision 2026-11-21-detail-reservation-contact).
        $host = $booking?->offer?->user;
        $hosting = $host !== null && (string) $host->getKey() === (string) request()->user()?->getAuthIdentifier();
        if ($booking !== null && is_array($event->data['booking']) && ($mine || $hosting)) {
            $other = $mine ? $host : $booking->user;
            $event->data['booking']['lines'] = [...$event->data['booking']['lines'], ...$this->contactLines($other, $mine ? 'host' : 'member')];
        }

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
