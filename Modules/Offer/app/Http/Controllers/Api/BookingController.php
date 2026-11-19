<?php

namespace Modules\Offer\Http\Controllers\Api;

use Illuminate\Http\Response;
use Modules\Auth\Models\User;
use Modules\Offer\Forms\BookingAnswerForm;
use Modules\Offer\Http\Requests\BookingAnswerRequest;
use Modules\Offer\Http\Requests\OfferBookingRequest;
use Modules\Offer\Models\Booking;
use Modules\Offer\Models\Offer;
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
     * Accepter ou refuser une demande en attente sur une de ses offres.
     */
    public function answer(BookingAnswerRequest $request, Booking $booking): Response
    {
        /** @var array{answer: string, refusal_message?: string|null} $data */
        $data = $request->validated();
        $this->bookings->answer($booking, $data['answer'] === BookingAnswerForm::ACCEPT, $data['refusal_message'] ?? null);

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
     * Annuler sa demande, en attente ou acceptée et pas encore commencée.
     */
    public function cancel(Booking $booking): Response
    {
        $this->bookings->cancel($booking);

        return response()->noContent();
    }
}
