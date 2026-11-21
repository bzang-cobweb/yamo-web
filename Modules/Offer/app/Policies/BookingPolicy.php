<?php

namespace Modules\Offer\Policies;

use Filament\Facades\Filament;
use Illuminate\Auth\Access\Response;
use Modules\Auth\Enums\PermissionEnum;
use Modules\Auth\Models\User;
use Modules\Auth\Policies\Concerns\HasBeforeAbility;
use Modules\Offer\Models\Booking;

/**
 * Les demandes de réservation (décision 2026-11-19-contrat-reservations). Au
 * back-office : en lecture, pour qui voit les offres ; jamais créées, modifiées
 * ni supprimées (la décision reste au propriétaire). Dans l'app : le membre annule
 * la sienne (`cancel`), le propriétaire de l'offre répond (`answer`).
 */
class BookingPolicy
{
    use HasBeforeAbility { before as superAdminBefore; }

    /**
     * Le super administrateur voit tout, mais ne crée, ne modifie, ne supprime, ni
     * ne répond ou n'annule à la place des intéressés.
     */
    public function before(User $user, string $ability): ?bool
    {
        return in_array($ability, ['viewAny', 'view'], true) ? $this->superAdminBefore($user, $ability) : null;
    }

    public function viewAny(User $user): bool
    {
        return Filament::isServing() && $user->hasPermissionTo(PermissionEnum::VIEW_OFFERS);
    }

    public function view(User $user, Booking $booking): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, Booking $booking): bool
    {
        return false;
    }

    public function delete(User $user, Booking $booking): bool
    {
        return false;
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }

    /**
     * Annuler sa demande : la sienne (sinon 404), encore annulable (sinon 403).
     */
    public function cancel(User $user, Booking $booking): Response
    {
        if ((int) $booking->user_id !== (int) $user->getKey()) {
            return Response::denyAsNotFound();
        }

        return $booking->isCancellable() ? Response::allow() : Response::deny(__('offer::booking.not_cancellable'));
    }

    /**
     * Modifier sa demande (sinon 404), encore en attente (sinon 403) (décision 2026-11-21-modifier-reservation).
     */
    public function edit(User $user, Booking $booking): Response
    {
        if ((int) $booking->user_id !== (int) $user->getKey()) {
            return Response::denyAsNotFound();
        }

        return $booking->isAwaitingAnswer() ? Response::allow() : Response::deny(__('offer::booking.not_editable'));
    }

    /**
     * Répondre à une demande : sur une de ses offres (sinon 404), encore en attente (sinon 403).
     */
    public function answer(User $user, Booking $booking): Response
    {
        if ((int) $booking->offer->user_id !== (int) $user->getKey()) {
            return Response::denyAsNotFound();
        }

        return $booking->isAwaitingAnswer() ? Response::allow() : Response::deny(__('offer::booking.already_answered'));
    }
}
