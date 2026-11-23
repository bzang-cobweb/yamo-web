<?php

namespace Modules\Offer\Http\Controllers\Api;

use Illuminate\Http\Response;
use Modules\Auth\Models\User;
use Modules\Offer\Forms\BookingAnswerForm;
use Modules\Offer\Http\Requests\BookingAnswerRequest;
use Modules\Offer\Http\Requests\BookingCancelRequest;
use Modules\Offer\Http\Requests\BookingUpdateRequest;
use Modules\Offer\Http\Requests\OfferBookingRequest;
use Modules\Offer\Models\Booking;
use Modules\Offer\Models\Offer;
use Modules\Offer\Notifications\BookingReviewInvitation;
use Modules\Offer\Services\BookingService;

/**
 * Les demandes de réservation ou de visite (décision 2026-11-19-contrat-reservations) :
 * demander (un membre, sur l'offre d'un autre), répondre (le propriétaire),
 * annuler (le membre). Les réponses sont vides : l'app affiche le message du
 * formulaire et recharge ses listes.
 */
class BookingController
{
    public function __construct(private readonly BookingService $bookings) {}

    /**
     * Demander un séjour ou une visite.
     */
    public function store(OfferBookingRequest $request, Offer $offer): Response
    {
        /** @var User $user */
        $user = $request->user();
        $this->bookings->request($user, $offer, $request->validated());

        return response()->noContent();
    }

    /**
     * Modifier sa demande en attente : dates, voyageurs, message (décision 2026-11-21-modifier-reservation).
     */
    public function update(BookingUpdateRequest $request, Booking $booking): Response
    {
        $this->bookings->update($booking, $request->validated());

        return response()->noContent();
    }

    /**
     * Accepter ou refuser une demande en attente sur une de ses offres.
     */
    public function answer(BookingAnswerRequest $request, Booking $booking): Response
    {
        /** @var array{answer: string, refusal_message?: string|null, acceptance_message?: string|null} $data */
        $data = $request->validated();
        $accept = $data['answer'] === BookingAnswerForm::ACCEPT;
        $this->bookings->answer($booking, $accept, $accept ? ($data['acceptance_message'] ?? null) : ($data['refusal_message'] ?? null));

        return response()->noContent();
    }

    /**
     * Accepter une demande en attente, sans formulaire (le bouton de sa carte).
     */
    public function accept(Booking $booking): Response
    {
        $this->bookings->answer($booking, true);

        return response()->noContent();
    }

    /**
     * Inviter le membre à donner son avis, depuis la page d'une demande terminée (décision
     * 2026-11-22-inviter-avis-depuis-la-demande) : la même invitation que la tâche du matin.
     */
    public function inviteReview(Booking $booking): Response
    {
        $booking->user->notify(new BookingReviewInvitation($booking));
        $booking->forceFill(['review_invited_at' => now()])->save();

        return response()->noContent();
    }

    /**
     * Afficher l'adresse du logement au demandeur (décision 2026-11-21-afficher-adresse).
     */
    public function showAddress(Booking $booking): Response
    {
        $this->bookings->showAddress($booking, true);

        return response()->noContent();
    }

    /**
     * Masquer l'adresse du logement au demandeur (décision 2026-11-21-afficher-adresse).
     */
    public function hideAddress(Booking $booking): Response
    {
        $this->bookings->showAddress($booking, false);

        return response()->noContent();
    }

    /**
     * Annuler sa demande, en attente ou acceptée et pas encore commencée.
     */
    public function cancel(BookingCancelRequest $request, Booking $booking): Response
    {
        $message = $request->validated('message');
        $this->bookings->cancel($booking, is_string($message) ? $message : null);

        return response()->noContent();
    }
}
