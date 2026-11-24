<?php

namespace Modules\Offer\Listeners;

use Modules\Offer\Enums\BookingStepEnum;
use Modules\Offer\Events\BookingAnswered;
use Modules\Offer\Events\BookingCancelled;
use Modules\Offer\Events\BookingRequested;
use Modules\Offer\Events\BookingUpdated;
use Modules\Offer\Services\BookingNotifierService;

/**
 * Chaque action d'une partie lui est confirmée par e-mail et SMS, sans notification dans l'onglet (décision
 * 2026-11-24-confirmation-auteur) : le membre pour une demande, une modification ou une annulation, le propriétaire
 * pour une réponse.
 */
final class ConfirmBookingActionToActor
{
    public function __construct(
        private readonly BookingNotifierService $bookingNotifierService,
    ) {}

    public function handle(BookingRequested|BookingUpdated|BookingAnswered|BookingCancelled $event): void
    {
        // 1. Réunir : l'étape de l'action, et si une modification a changé son message.
        $step = match (true) {
            $event instanceof BookingRequested => BookingStepEnum::RECEIVED,
            $event instanceof BookingUpdated => BookingStepEnum::UPDATED,
            $event instanceof BookingCancelled => BookingStepEnum::CANCELLED,
            default => BookingStepEnum::answer($event->booking->status),
        };
        $edited = $event instanceof BookingUpdated && $event->edited;

        // 2. Appeler le service.
        $this->bookingNotifierService->confirmToActor($event->booking, $step, $edited);
    }
}
