<?php

namespace Modules\Offer\Services;

use Modules\Auth\Models\User;
use Modules\Auth\Support\Masks;
use Modules\Offer\Forms\BookingCancelForm;
use Modules\Offer\Forms\BookingEditForm;
use Modules\Offer\Forms\BookingInviteReviewForm;
use Modules\Offer\Http\Resources\BookingResource;
use Modules\Offer\Notifications\BookingNotification;
use Modules\Offer\Support\BookingCache;
use Modules\Offer\Support\OfferHeading;

/**
 * Sur la page d'une notification du fil d'une demande (`booking:12`), la demande en
 * léger, sous `booking` (décision 2026-11-20-notification-generique). Une demande
 * supprimée : `booking` vaut `null`. Pour le membre qui l'a faite, tant qu'elle est en
 * attente, le bouton du bas ouvre « Modifier la demande » au lieu de « Mes réservations »
 * (décision 2026-11-21-detail-reservation-modifier) ; tant qu'il peut l'annuler, un second bouton,
 * « Annuler », ouvre son formulaire d'annulation (décision 2026-11-21-annuler-depuis-le-detail).
 */
class BookingThreadDetailsService
{
    public function __construct(
        private readonly BookingCache $cache,
    ) {}

    /**
     * La page de la notification, complétée de la demande `$bookingId` telle que `$reader` la voit.
     *
     * @param  int|null  $bookingId  la demande du fil (`null` : identifiant illisible, comme une demande supprimée)
     * @param  array<string, mixed>  $data  la page de la notification
     * @return array<string, mixed> la page, avec `booking`, et selon le lecteur le titre, les lignes et les boutons
     */
    public function forThread(?int $bookingId, array $data, ?User $reader): array
    {
        $readerId = (string) $reader?->getKey();
        $booking = $bookingId === null ? null : $this->cache->find($bookingId);

        $data['booking'] = $booking === null ? null : (new BookingResource($booking))->resolve(request());
        // Le titre du panneau de « Voir plus » : toutes les lignes de la demande (décision 2026-11-26-lignes-en-panneau).
        if ($booking !== null) {
            $data['lines_title'] = (string) __('offer::booking.detail.lines_title', ['request' => BookingNotification::requestLabel($booking->type)]);
        }

        $mine = $booking !== null && (string) $booking->user_id === $readerId;

        // Le titre de la barre du haut : « Ma réservation » pour le membre, « Demande reçue » pour le propriétaire
        // (décision 2026-11-22-detail-titre-compose-et-dates) ; le titre composé est la première ligne de la demande
        // (décision 2026-11-22-detail-lignes-titre-dates-adresse).
        if ($booking !== null) {
            $owner = (string) $booking->offer?->user_id === $readerId;
            $data['title'] = $mine ? __('offer::booking.detail.mine') : ($owner ? __('offer::booking.detail.received', ['request' => BookingNotification::requestLabel($booking->type)]) : __('offer::booking.detail_title.'.$booking->type->value));
        }

        // L'en-tête de la page : le nom du logement, en lien vers sa fiche, et son titre composé (« Location meublée ·
        // Studio · Douala »), comme pour une discussion avec l'éditeur (`OfferHeading`) ; la demande n'apporte que ses lignes.
        $heading = OfferHeading::of($booking?->offer, app()->getLocale());
        if ($heading !== null) {
            $data['heading'] = $heading;
        }

        // L'adresse masquée par le propriétaire : pas de ligne « Adresse » pour le demandeur, pas même la ville
        // (décisions 2026-11-21-afficher-adresse et 2026-11-24-adresse-masquee-sans-ville).
        if ($mine && ! $booking->show_address && is_array($data['booking'])) {
            $data['booking']['lines'] = array_values(array_filter(
                $data['booking']['lines'],
                fn (array $line): bool => ($line['key'] ?? null) !== 'address',
            ));
        }

        // L'autre partie, juste après l'adresse, toujours (décisions 2026-11-21-afficher-adresse,
        // 2026-11-21-contact-toujours et 2026-11-21-detail-reservation-contact-ordre) : pour le membre qui l'a faite,
        // le téléphone et l'e-mail de l'offre, sinon ceux de l'hôte (décision 2026-11-21-contact-offre) ; pour
        // l'hôte, le nom du membre vers sa page, son téléphone et son e-mail.
        $host = $booking?->offer?->user;
        $hosting = $host !== null && (string) $host->getKey() === $readerId;
        if ($booking !== null && is_array($data['booking']) && ($mine || $hosting)) {
            $offer = $booking->offer;
            $contact = $mine
                ? $this->contactLines(null, null, $offer?->phone ?? $host?->phone, $offer?->email ?? $host?->email)
                : $this->contactLines('member', $booking->user, $booking->user?->phone, $booking->user?->email);
            $data['booking']['lines'] = $this->afterAddress($data['booking']['lines'], $contact);
        }

        if ($mine && $booking->isCancellable()) {
            $data['secondary_link'] = '/profile/'.BookingCancelForm::name().'?record='.$booking->getKey();
            $data['secondary_link_label'] = __('offer::booking.cancel.button');
        }

        // Terminée, sans avis du membre : « Laisser un avis » ouvre le formulaire d'avis du logement (décision
        // 2026-11-22-avis-apres-demande-terminee).
        if ($mine && $booking->isCompleted() && ! $booking->offer?->reviews()->where('user_id', $booking->user_id)->exists()) {
            $data['link'] = '/offers/'.$booking->offer_id.'/review';
            $data['link_label'] = __('offer::booking.review_cta');
        }

        // Le propriétaire : « Traiter la demande » seulement tant qu'elle attend une réponse (décision
        // 2026-11-22-traiter-si-en-attente).
        if ($hosting && ! $mine && ! $booking->isAwaitingAnswer()) {
            $data['link'] = null;
            $data['link_label'] = null;
        }

        // Terminée, sans avis du membre : « Inviter à donner un avis », à la même place (décision
        // 2026-11-22-inviter-avis-depuis-la-demande).
        if ($hosting && ! $mine && $booking->canInviteReview()) {
            $data['link'] = '/profile/'.BookingInviteReviewForm::name().'?record='.$booking->getKey();
            $data['link_label'] = __('offer::booking.invite_review.button');
        }

        if ($mine && $booking->isAwaitingAnswer()) {
            $data['link'] = '/profile/'.BookingEditForm::name().'?record='.$booking->getKey();
            $data['link_label'] = __('offer::booking.form.edit');
        }

        return $data;
    }

    /**
     * Le nom (avec le lien de sa page, si `$role` est donné), le téléphone et l'e-mail ; une ligne sans valeur
     * est omise. Le téléphone et l'e-mail sont affichés masqués, leur lien (`tel:`, `mailto:`) garde la vraie valeur
     * pour appeler ou écrire (décision 2026-11-27-email-telephone-masques).
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
            $phone === null || $phone === '' ? null : $line('phone', Masks::phone($phone), 'tel:'.$phone),
            $email === null || $email === '' ? null : $line('email', Masks::email($email), 'mailto:'.$email),
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
        // Après l'adresse ; sans adresse, après les dates (décision
        // 2026-11-22-detail-lignes-titre-dates-adresse).
        $at = 0;
        foreach ($lines as $index => $line) {
            if (in_array($line['key'] ?? null, ['arrival', 'departure', 'visit', 'address'], true)) {
                $at = $index + 1;
            }
        }

        return [...array_slice($lines, 0, $at), ...$contact, ...array_slice($lines, $at)];
    }
}
