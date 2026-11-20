<?php

namespace Modules\Offer\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Modules\Offer\Events\BookingAnswered;
use Modules\Offer\Events\BookingCancelled;
use Modules\Offer\Events\BookingRequested;
use Modules\Offer\Listeners\NotifyGuestOfBookingAnswer;
use Modules\Offer\Listeners\NotifyOwnerOfBookingCancellation;
use Modules\Offer\Listeners\NotifyOwnerOfBookingRequest;
use Modules\Offer\Listeners\NotifyOwnerOfOwnAnswer;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event handler mappings for the application.
     *
     * @var array<string, array<int, string>>
     */
    protected $listen = [
        // Réservations : chaque étape prévient l'autre partie (décision 2026-11-19-notifications-reservations).
        BookingRequested::class => [NotifyOwnerOfBookingRequest::class],
        BookingAnswered::class => [NotifyGuestOfBookingAnswer::class, NotifyOwnerOfOwnAnswer::class],
        BookingCancelled::class => [NotifyOwnerOfBookingCancellation::class],
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
