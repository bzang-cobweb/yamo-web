<?php

namespace Modules\Offer\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Modules\Notification\Events\NotificationThreadSerializing;
use Modules\Offer\Events\BookingAnswered;
use Modules\Offer\Events\BookingCancelled;
use Modules\Offer\Events\BookingRequested;
use Modules\Offer\Events\BookingUpdated;
use Modules\Offer\Listeners\AddBookingToNotificationThread;
use Modules\Offer\Listeners\ConfirmBookingActionToActor;
use Modules\Offer\Listeners\NotifyGuestOfBookingAnswer;
use Modules\Offer\Listeners\NotifyOwnerOfBookingCancellation;
use Modules\Offer\Listeners\NotifyOwnerOfBookingRequest;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event handler mappings for the application.
     *
     * @var array<string, array<int, string>>
     */
    protected $listen = [
        // Réservations : chaque étape prévient l'autre partie (décision 2026-11-19-notifications-reservations).
        // Le membre garde sa demande et son annulation dans son fil (décision 2026-11-21-reservation-comme-notification).
        BookingRequested::class => [NotifyOwnerOfBookingRequest::class, ConfirmBookingActionToActor::class],
        BookingAnswered::class => [NotifyGuestOfBookingAnswer::class, ConfirmBookingActionToActor::class],
        BookingCancelled::class => [NotifyOwnerOfBookingCancellation::class, ConfirmBookingActionToActor::class],
        BookingUpdated::class => [NotifyOwnerOfBookingRequest::class, ConfirmBookingActionToActor::class],
        // La page d'une notification du fil d'une demande : la demande (décision 2026-11-20-notification-generique).
        NotificationThreadSerializing::class => [AddBookingToNotificationThread::class],
    ];

    /**
     * Indicates if events should be discovered.
     *
     * @var bool
     */
    protected static $shouldDiscoverEvents = false;

    /**
     * Configure the proper event listeners for email verification.
     */
    protected function configureEmailVerification(): void {}
}
