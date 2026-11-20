<?php

namespace Modules\Offer\Tests\Feature;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Modules\Auth\Models\User;
use Modules\Auth\Notifications\Channels\SmsChannel;
use Modules\Auth\Sms\SmsSender;
use Modules\Auth\Tests\Concerns\FakeSmsSender;
use Modules\Location\Models\Country;
use Modules\Offer\Database\Seeders\OfferDatabaseSeeder;
use Modules\Offer\Enums\BookingStatusEnum;
use Modules\Offer\Events\BookingAnswered;
use Modules\Offer\Events\BookingCancelled;
use Modules\Offer\Events\BookingRequested;
use Modules\Offer\Models\Booking;
use Modules\Offer\Models\Offer;
use Modules\Offer\Notifications\BookingRequestAccepted;
use Modules\Offer\Notifications\BookingRequestReceived;
use Modules\Offer\Services\BookingService;
use Tests\TestCase;

/**
 * Notifications des réservations (décision 2026-11-19-notifications-reservations) :
 * chaque étape est un événement ; la notification, mise en file après
 * l'enregistrement, part sur les canaux du destinataire, dans sa langue.
 */
class BookingNotificationsTest extends TestCase
{
    use RefreshDatabase;

    private FakeSmsSender $sms;

    protected function setUp(): void
    {
        parent::setUp();

        config(['offer.domain' => 'rental']);
        Country::factory()->create(['code' => 'CM', 'currency' => 'XAF']);
        $this->seed(OfferDatabaseSeeder::class);
        $this->sms = new FakeSmsSender;
        $this->app->instance(SmsSender::class, $this->sms);
    }

    private function booking(array $owner = [], array $guest = []): Booking
    {
        $offer = Offer::factory()->for(User::factory()->create(['locale' => 'fr', 'email_verified_at' => now(), ...$owner]), 'user')->create([
            'status' => 'published', 'price' => '45000', 'currency' => 'XAF',
            'details' => ['property_type' => 'studio', 'furnished' => true],
        ]);

        return Booking::factory()->for($offer)->for(User::factory()->create(['first_name' => 'Marie', 'last_name' => 'Curie', 'locale' => 'fr', 'email_verified_at' => now(), ...$guest]))
            ->create(['check_in' => '2027-03-03', 'check_out' => '2027-03-06', 'message' => 'Bonjour !']);
    }

    public function test_each_step_is_an_event(): void
    {
        Event::fake([BookingRequested::class, BookingAnswered::class, BookingCancelled::class]);
        $service = app(BookingService::class);
        $booking = $this->booking();

        $requested = $service->request($booking->user, $booking->offer, ['check_in' => today()->addDay()->toDateString(), 'check_out' => today()->addDays(3)->toDateString(), 'guests' => 1]);
        Event::assertDispatched(BookingRequested::class, fn (BookingRequested $event): bool => $event->booking->is($requested));

        $service->answer($booking, true);
        Event::assertDispatched(BookingAnswered::class, fn (BookingAnswered $event): bool => $event->booking->is($booking));

        $service->cancel($booking);
        Event::assertDispatched(BookingCancelled::class);
    }

    public function test_notifications_are_queued_after_commit(): void
    {
        $notification = new BookingRequestReceived($this->booking());

        $this->assertInstanceOf(ShouldQueue::class, $notification);
        $this->assertTrue($notification->afterCommit);
    }

    public function test_channels_follow_the_enabled_channels_and_what_is_verified(): void
    {
        $user = User::factory()->make(['email' => 'a@example.test', 'email_verified_at' => now(), 'phone' => '+237690000001', 'phone_verified_at' => now()]);
        $this->assertSame(['mail', SmsChannel::class], $user->notificationChannels());

        $user->phone_verified_at = null;
        $this->assertSame(['mail'], $user->notificationChannels());

        // Le SMS fermé par l'application : plus de SMS, même vérifié.
        $user->phone_verified_at = now();
        config(['auth.channels.phone' => false]);
        $this->assertSame(['mail'], $user->notificationChannels());
    }

    public function test_the_mail_and_sms_read_the_booking_in_the_recipient_language(): void
    {
        $booking = $this->booking();

        app()->setLocale('fr');
        $mail = (new BookingRequestReceived($booking))->toMail($booking->offer->user);
        $this->assertSame('Nouvelle demande : '.$booking->offer->getTranslation('title', 'fr'), $mail->subject);
        $this->assertStringContainsString('Marie Curie demande un séjour du 3 mars 2027 au 6 mars 2027', $mail->introLines[0]);
        $this->assertSame('Message : Bonjour !', $mail->introLines[1]);
        $this->assertStringContainsString('« Réservations reçues »', $mail->introLines[2]);

        app()->setLocale('en');
        $this->assertStringContainsString('your request for', (new BookingRequestAccepted($booking))->toSms($booking->user));
    }

    public function test_the_real_send_reaches_the_sms_sender_in_the_recipient_language(): void
    {
        // File synchrone en test : la notification part après l'enregistrement.
        $booking = $this->booking(owner: ['locale' => 'en', 'phone' => '+237690000009', 'phone_verified_at' => now()]);

        app(BookingService::class)->request($booking->user, $booking->offer, ['check_in' => today()->addDay()->toDateString(), 'check_out' => today()->addDays(2)->toDateString(), 'guests' => 1]);

        $this->assertCount(1, $this->sms->messages);
        $this->assertSame('+237690000009', $this->sms->messages[0]['to']);
        $this->assertStringContainsString('Marie Curie requests a stay', $this->sms->messages[0]['message']);
    }

    public function test_the_notification_status_is_the_one_at_send_time(): void
    {
        $booking = $this->booking();
        $notification = new BookingRequestReceived($booking);
        $booking->forceFill(['status' => BookingStatusEnum::ACCEPTED])->save();

        $data = unserialize(serialize($notification))->toArray($booking->offer->user);

        $this->assertSame(['label' => 'En attente', 'tone' => 'warning'], $data['tag']);
        $this->assertSame('Demande de logement', $data['title']);
    }

    public function test_an_accepted_answer_reaches_the_guest(): void
    {
        $booking = $this->booking(guest: ['phone' => '+237690000008', 'phone_verified_at' => now()]);

        app(BookingService::class)->answer($booking, true);

        $this->assertSame(BookingStatusEnum::ACCEPTED, $booking->fresh()->status);
        $this->assertSame('+237690000008', $this->sms->messages[0]['to']);
        $this->assertStringContainsString('est acceptée', $this->sms->messages[0]['message']);
    }

    public function test_a_request_lands_in_the_owner_notifications_tab_in_their_language(): void
    {
        // Décision 2026-11-19-contrat-centre-notifications : toujours, même sans e-mail ni SMS.
        $booking = $this->booking(owner: ['locale' => 'en', 'email_verified_at' => null]);
        $owner = $booking->offer->user;

        app(BookingService::class)->request($booking->user, $booking->offer, ['check_in' => today()->addDay()->toDateString(), 'check_out' => today()->addDays(2)->toDateString(), 'guests' => 1, 'message' => 'Bonjour, je viens pour le travail.']);

        $notification = $owner->notifications()->sole();
        $this->assertSame('Home request', $notification->data['title']);
        $this->assertSame(['label' => 'Pending', 'tone' => 'warning'], $notification->data['tag']);
        $this->assertStringContainsString('Marie Curie requests a stay', $notification->data['body']);
        $this->assertSame('/offers/'.$booking->offer_id.'/calendar?month='.today()->addDay()->format('Y-m'), $notification->data['link']);
        $this->assertSame([], $notification->data['links']);
        $this->assertSame('Handle the request', $notification->data['link_label']);
        $this->assertSame($booking->user_id, (int) $notification->user_id);
        $this->assertArrayNotHasKey('actor_id', $notification->data);
        $this->assertStringStartsWith('requests a stay', $notification->data['summary']);
        $this->assertSame($booking->offer_id, $notification->data['offer_id']);
        $this->assertSame([
            ['text' => 'Marie Curie', 'link' => '/members/'.$booking->user_id],
            ['text' => $booking->offer->getTranslation('title', 'en'), 'link' => '/offers/'.$booking->offer_id],
        ], $notification->data['text_links']);
        $this->assertSame('Bonjour, je viens pour le travail.', $notification->data['message']);
        $this->assertNull($notification->read_at);
    }
}
