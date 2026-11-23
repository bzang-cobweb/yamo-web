<?php

namespace Modules\Offer\Listeners;

use Illuminate\Support\Str;
use Modules\Auth\Models\User;
use Modules\Auth\Support\PhoneNumbers;
use Modules\Notification\Events\NotificationThreadSerializing;
use Modules\Offer\Forms\BookingCancelForm;
use Modules\Offer\Forms\BookingEditForm;
use Modules\Offer\Forms\BookingInviteReviewForm;
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
     * Le nom (avec le lien de sa page, si `$role` est donné), le téléphone et l'e-mail ; une ligne sans valeur
     * est omise.
     *
     * @return list<array{key: string, label: string, value: string, link?: string}>
     */
    private function contactLines(?string $role, ?User $person, ?string $phone, ?string $email): array
    {
        $line = fn (string $key, ?string $value, ?string $link = null): ?array => $value === null || $value === ''
            ? null
            : ['key' => $key, 'label' => __('offer::booking.lines.'.$key), 'value' => $value, ...($link === null ? [] : ['link' => $link])];

        return array_values(array_filter([
            $role === null || $person === null ? null : $line($role, (string) $person->full_name, '/members/'.$person->getKey()),
            $phone === null || $phone === '' ? null : $line('phone', PhoneNumbers::international($phone)),
            $line('email', $email),
        ]));
    }

    /**
     * Place `$contact` juste après l'adresse ; sans adresse, en tête.
     *
     * @param  list<array<string, mixed>>  $lines
     * @param  list<array<string, mixed>>  $contact
     * @return list<array<string, mixed>>
     */
    private function afterAddress(array $lines, array $contact): array
    {
        $at = 0;
        foreach ($lines as $index => $line) {
            if (($line['key'] ?? null) === 'address') {
                $at = $index + 1;
            }
        }

        return [...array_slice($lines, 0, $at), ...$contact, ...array_slice($lines, $at)];
    }

    public function handle(NotificationThreadSerializing $event): void
    {
        if (! str_starts_with($event->thread, Booking::THREAD_PREFIX)) {
            return;
        }

        $id = Str::after($event->thread, Booking::THREAD_PREFIX);
        $booking = ctype_digit($id) ? app(BookingCache::class)->find((int) $id) : null;

        $event->data['booking'] = $booking === null ? null : (new BookingResource($booking))->resolve(request());

        // Le titre de la page : le titre composé sans ville (« Location meublée · Studio », décision
        // 2026-11-22-detail-titre-compose) ; sans type, selon la demande, « Demande de séjour » ou « Demande de visite »
        // (décision 2026-11-22-detail-titre-sejour-visite).
        if ($booking !== null && is_array($event->data['booking'])) {
            $offer = $event->data['booking']['offer'] ?? [];
            $composed = implode(' · ', array_filter([$offer['rental_type'] ?? null, $offer['property_type'] ?? null]));
            $event->data['title'] = $composed !== '' ? $composed : __('offer::booking.detail_title.'.$booking->type->value);
        }

        $mine = $booking !== null && (string) $booking->user_id === (string) request()->user()?->getAuthIdentifier();

        // L'adresse masquée par le propriétaire : la ville seule pour le demandeur ; sans ville, pas de ligne
        // (décisions 2026-11-21-afficher-adresse et 2026-11-21-adresse-masquee-ville).
        if ($mine && ! $booking->show_address && is_array($event->data['booking'])) {
            $city = $booking->offer?->address?->city;
            $event->data['booking']['lines'] = array_values(array_filter(array_map(
                fn (array $line): ?array => ($line['key'] ?? null) !== 'address' ? $line : ($city === null || $city === '' ? null : [...$line, 'value' => $city]),
                $event->data['booking']['lines'],
            )));
        }

        // L'autre partie, juste après l'adresse, toujours (décisions 2026-11-21-afficher-adresse,
        // 2026-11-21-contact-toujours et 2026-11-21-detail-reservation-contact-ordre) : pour le membre qui l'a faite,
        // le téléphone et l'e-mail de l'offre, sinon ceux de l'hôte (décision 2026-11-21-contact-offre) ; pour
        // l'hôte, le nom du membre vers sa page, son téléphone et son e-mail.
        $host = $booking?->offer?->user;
        $hosting = $host !== null && (string) $host->getKey() === (string) request()->user()?->getAuthIdentifier();
        if ($booking !== null && is_array($event->data['booking']) && ($mine || $hosting)) {
            $offer = $booking->offer;
            $contact = $mine
                ? $this->contactLines(null, null, $offer?->phone ?? $host?->phone, $offer?->email ?? $host?->email)
                : $this->contactLines('member', $booking->user, $booking->user?->phone, $booking->user?->email);
            $event->data['booking']['lines'] = $this->afterAddress($event->data['booking']['lines'], $contact);
        }

        if ($mine && $booking->isCancellable()) {
            $event->data['secondary_link'] = '/profile/'.BookingCancelForm::name().'?record='.$booking->getKey();
            $event->data['secondary_link_label'] = __('offer::booking.cancel.button');
        }

        // Terminée, sans avis du membre : « Laisser un avis » ouvre le formulaire d'avis du logement (décision
        // 2026-11-22-avis-apres-demande-terminee).
        if ($mine && $booking->isCompleted() && ! $booking->offer?->reviews()->where('user_id', $booking->user_id)->exists()) {
            $event->data['link'] = '/offers/'.$booking->offer_id.'/review';
            $event->data['link_label'] = __('offer::booking.review_cta');
        }

        // Le propriétaire : « Traiter la demande » seulement tant qu'elle attend une réponse (décision
        // 2026-11-22-traiter-si-en-attente).
        if ($hosting && ! $mine && ! $booking->isAwaitingAnswer()) {
            $event->data['link'] = null;
            $event->data['link_label'] = null;
        }

        // Terminée, sans avis du membre : « Inviter à donner un avis », à la même place (décision
        // 2026-11-22-inviter-avis-depuis-la-demande).
        if ($hosting && ! $mine && $booking->canInviteReview()) {
            $event->data['link'] = '/profile/'.BookingInviteReviewForm::name().'?record='.$booking->getKey();
            $event->data['link_label'] = __('offer::booking.invite_review.button');
        }

        if ($mine && $booking->isAwaitingAnswer()) {
            $event->data['link'] = '/profile/'.BookingEditForm::name().'?record='.$booking->getKey();
            $event->data['link_label'] = __('offer::booking.form.edit');
        }
    }
}
