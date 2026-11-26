<?php

namespace Modules\Offer\Tests\Feature;

use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\Event;
use Mockery\MockInterface;
use Modules\Auth\Models\User;
use Modules\Notification\Events\NotificationThreadSerializing;
use Modules\Offer\Events\BookingAnswered;
use Modules\Offer\Events\BookingCancelled;
use Modules\Offer\Events\BookingRequested;
use Modules\Offer\Events\BookingUpdated;
use Modules\Offer\Listeners\AddBookingToNotificationThread;
use Modules\Offer\Listeners\NotifyGuestOfBookingAnswer;
use Modules\Offer\Listeners\NotifyOwnerOfBookingCancellation;
use Modules\Offer\Listeners\NotifyOwnerOfBookingRequest;
use Modules\Offer\Models\Booking;
use Modules\Offer\Notifications\OfferNotificationFilter;
use Modules\Offer\Services\BookingNotifierService;
use Modules\Offer\Services\BookingThreadDetailsService;
use Tests\TestCase;

/**
 * Les listeners des demandes ne font que réunir les données de l'évènement et appeler leur service
 * (décision 2026-11-23-listeners-vers-services).
 */
class BookingListenersTest extends TestCase
{
    public function test_each_booking_event_is_heard_by_its_listeners(): void
    {
        Event::fake();

        Event::assertListening(BookingRequested::class, NotifyOwnerOfBookingRequest::class);
        Event::assertListening(BookingUpdated::class, NotifyOwnerOfBookingRequest::class);
        Event::assertListening(BookingAnswered::class, NotifyGuestOfBookingAnswer::class);
        Event::assertListening(BookingCancelled::class, NotifyOwnerOfBookingCancellation::class);
        Event::assertListening(NotificationThreadSerializing::class, AddBookingToNotificationThread::class);
    }

    public function test_the_notify_listeners_call_the_notifier_with_the_booking(): void
    {
        $booking = new Booking;
        $this->mock(BookingNotifierService::class, function (MockInterface $bookingNotifierService) use ($booking): void {
            $bookingNotifierService->shouldReceive('ownerOfRequest')->once()->with($booking);
            $bookingNotifierService->shouldReceive('ownerOfUpdate')->once()->with($booking, true, true);
            $bookingNotifierService->shouldReceive('ownerOfCancellation')->once()->with($booking);
            $bookingNotifierService->shouldReceive('guestOfAnswer')->once()->with($booking);
        });

        app(NotifyOwnerOfBookingRequest::class)->handle(new BookingRequested($booking));
        app(NotifyOwnerOfBookingRequest::class)->handle(new BookingUpdated($booking, true, true));
        app(NotifyOwnerOfBookingCancellation::class)->handle(new BookingCancelled($booking));
        app(NotifyGuestOfBookingAnswer::class)->handle(new BookingAnswered($booking));
    }

    public function test_the_thread_listener_passes_the_booking_id_page_and_reader(): void
    {
        $reader = new User;
        request()->setUserResolver(fn (): User => $reader);
        $this->mock(BookingThreadDetailsService::class, function (MockInterface $bookingThreadDetailsService) use ($reader): void {
            $bookingThreadDetailsService->shouldReceive('forThread')->once()->with(12, ['title' => 'Avant'], $reader)->andReturn(['title' => 'Après']);
            // Un identifiant illisible : comme une demande supprimée.
            $bookingThreadDetailsService->shouldReceive('forThread')->once()->with(null, [], $reader)->andReturn(['booking' => null]);
        });

        $event = new NotificationThreadSerializing(new DatabaseNotification, OfferNotificationFilter::bookingThreadPrefix().'12', ['title' => 'Avant']);
        app(AddBookingToNotificationThread::class)->handle($event);
        $this->assertSame(['title' => 'Après'], $event->data);

        $unreadable = new NotificationThreadSerializing(new DatabaseNotification, OfferNotificationFilter::bookingThreadPrefix().'abc', []);
        app(AddBookingToNotificationThread::class)->handle($unreadable);
        $this->assertSame(['booking' => null], $unreadable->data);
    }

    public function test_the_thread_listener_ignores_other_threads(): void
    {
        $this->mock(BookingThreadDetailsService::class, fn (MockInterface $bookingThreadDetailsService) => $bookingThreadDetailsService->shouldNotReceive('forThread'));

        $event = new NotificationThreadSerializing(new DatabaseNotification, 'review:3', ['title' => 'Avis']);
        app(AddBookingToNotificationThread::class)->handle($event);

        $this->assertSame(['title' => 'Avis'], $event->data);
    }
}
