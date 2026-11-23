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
use Modules\Offer\Listeners\NotifyGuestOfOwnRequest;
use Modules\Offer\Listeners\NotifyOwnerOfBookingCancellation;
use Modules\Offer\Listeners\NotifyOwnerOfBookingRequest;
use Modules\Offer\Listeners\NotifyOwnerOfOwnAnswer;
use Modules\Offer\Models\Booking;
use Modules\Offer\Services\BookingNotifier;
use Modules\Offer\Services\BookingThreadDetails;
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
        Event::assertListening(BookingRequested::class, NotifyGuestOfOwnRequest::class);
        Event::assertListening(BookingUpdated::class, NotifyOwnerOfBookingRequest::class);
        Event::assertListening(BookingAnswered::class, NotifyGuestOfBookingAnswer::class);
        Event::assertListening(BookingAnswered::class, NotifyOwnerOfOwnAnswer::class);
        Event::assertListening(BookingCancelled::class, NotifyOwnerOfBookingCancellation::class);
        Event::assertListening(BookingCancelled::class, NotifyGuestOfOwnRequest::class);
        Event::assertListening(NotificationThreadSerializing::class, AddBookingToNotificationThread::class);
    }

    public function test_the_notify_listeners_call_the_notifier_with_the_booking(): void
    {
        $booking = new Booking;
        $this->mock(BookingNotifier::class, function (MockInterface $notifier) use ($booking): void {
            $notifier->shouldReceive('ownerOfRequest')->once()->with($booking);
            $notifier->shouldReceive('ownerOfUpdate')->once()->with($booking, true);
            $notifier->shouldReceive('ownerOfCancellation')->once()->with($booking);
            $notifier->shouldReceive('ownerOfOwnAnswer')->once()->with($booking);
            $notifier->shouldReceive('guestOfAnswer')->once()->with($booking);
            $notifier->shouldReceive('guestOfOwnRequest')->once()->with($booking);
            $notifier->shouldReceive('guestOfOwnCancellation')->once()->with($booking);
        });

        app(NotifyOwnerOfBookingRequest::class)->handle(new BookingRequested($booking));
        app(NotifyOwnerOfBookingRequest::class)->handle(new BookingUpdated($booking, true));
        app(NotifyOwnerOfBookingCancellation::class)->handle(new BookingCancelled($booking));
        app(NotifyOwnerOfOwnAnswer::class)->handle(new BookingAnswered($booking));
        app(NotifyGuestOfBookingAnswer::class)->handle(new BookingAnswered($booking));
        app(NotifyGuestOfOwnRequest::class)->handle(new BookingRequested($booking));
        app(NotifyGuestOfOwnRequest::class)->handle(new BookingCancelled($booking));
    }

    public function test_the_thread_listener_passes_the_booking_id_page_and_reader(): void
    {
        $reader = new User;
        request()->setUserResolver(fn (): User => $reader);
        $this->mock(BookingThreadDetails::class, function (MockInterface $details) use ($reader): void {
            $details->shouldReceive('forThread')->once()->with(12, ['title' => 'Avant'], $reader)->andReturn(['title' => 'Après']);
            // Un identifiant illisible : comme une demande supprimée.
            $details->shouldReceive('forThread')->once()->with(null, [], $reader)->andReturn(['booking' => null]);
        });

        $event = new NotificationThreadSerializing(new DatabaseNotification, Booking::THREAD_PREFIX.'12', ['title' => 'Avant']);
        app(AddBookingToNotificationThread::class)->handle($event);
        $this->assertSame(['title' => 'Après'], $event->data);

        $unreadable = new NotificationThreadSerializing(new DatabaseNotification, Booking::THREAD_PREFIX.'abc', []);
        app(AddBookingToNotificationThread::class)->handle($unreadable);
        $this->assertSame(['booking' => null], $unreadable->data);
    }

    public function test_the_thread_listener_ignores_other_threads(): void
    {
        $this->mock(BookingThreadDetails::class, fn (MockInterface $details) => $details->shouldNotReceive('forThread'));

        $event = new NotificationThreadSerializing(new DatabaseNotification, 'review:3', ['title' => 'Avis']);
        app(AddBookingToNotificationThread::class)->handle($event);

        $this->assertSame(['title' => 'Avis'], $event->data);
    }
}
