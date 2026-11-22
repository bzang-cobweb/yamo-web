<?php

namespace Modules\Offer\Tests\Feature;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Modules\Auth\Models\User;
use Modules\Auth\Notifications\Channels\SmsChannel;
use Modules\Auth\Sms\SmsSender;
use Modules\Auth\Tests\Concerns\FakeSmsSender;
use Modules\Location\Models\Country;
use Modules\Offer\Database\Seeders\OfferDatabaseSeeder;
use Modules\Offer\Enums\BookingStatusEnum;
use Modules\Offer\Enums\BookingStepEnum;
use Modules\Offer\Events\BookingAnswered;
use Modules\Offer\Events\BookingCancelled;
use Modules\Offer\Events\BookingRequested;
use Modules\Offer\Models\Booking;
use Modules\Offer\Models\Offer;
use Modules\Offer\Notifications\BookingNotification;
use Modules\Offer\Notifications\BookingRequestReceived;
use Modules\Offer\Notifications\BookingRequestRefused;
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
            'details' => ['property_type' => 'studio', 'rental_type' => 'furnished'],
        ]);

        return Booking::factory()->for($offer)->for(User::factory()->create(['first_name' => 'Marie', 'last_name' => 'Curie', 'locale' => 'fr', 'email_verified_at' => now(), ...$guest]))
            ->create(['check_in' => '2027-03-03', 'check_out' => '2027-03-06', 'message' => 'Bonjour !']);
    }

    public function test_the_cancellation_names_a_visit_or_a_stay_request(): void
    {
        // Décision 2026-11-22-annulation-visite-sejour.
        $stay = $this->booking();
        $visit = $this->booking();
        $visit->forceFill(['type' => 'visit', 'check_out' => null])->save();
        app()->setLocale('fr');

        foreach (['séjour' => $stay, 'visite' => $visit] as $kind => $booking) {
            $owner = $booking->offer->user;
            $guest = (string) $booking->user->full_name;
            $sent = BookingNotification::for($booking, BookingStepEnum::CANCELLED);
            $own = BookingNotification::for($booking, BookingStepEnum::CANCELLED, own: true);

            $this->assertStringStartsWith("La demande de {$kind} pour « ", $sent->toArray($owner)['summary']);
            $this->assertStringEndsWith(') a été annulée.', $sent->toArray($owner)['summary']);
            $this->assertStringStartsWith("Vous avez annulé votre demande de {$kind} pour « ", $own->toArray($booking->user)['body']);
            $this->assertStringContainsString("{$guest} a annulé sa demande de {$kind} pour « ", $sent->toSms($owner));
        }
    }

    public function test_the_answer_names_a_visit_or_a_stay_request(): void
    {
        // Décision 2026-11-22-reponse-visite-sejour.
        $stay = $this->booking();
        $visit = $this->booking();
        $visit->forceFill(['type' => 'visit', 'check_out' => null])->save();
        app()->setLocale('fr');

        foreach (['séjour' => $stay, 'visite' => $visit] as $kind => $booking) {
            foreach ([BookingStepEnum::ACCEPTED->value => 'acceptée', BookingStepEnum::REFUSED->value => 'refusée'] as $step => $done) {
                $owner = $booking->offer->user;
                $guest = (string) $booking->user->full_name;
                $sent = BookingNotification::for($booking, BookingStepEnum::from($step));
                $own = BookingNotification::for($booking, BookingStepEnum::from($step), own: true);
                $verb = mb_substr($done, 0, -1);

                $this->assertStringStartsWith("Votre demande de {$kind} pour « ", $sent->toArray($booking->user)['summary']);
                $this->assertStringEndsWith(($kind === 'séjour' ? ' personnes)' : ')')." a été {$done}.", $sent->toArray($booking->user)['summary']);
                $this->assertStringStartsWith("Vous avez {$verb} la demande de {$kind} de {$guest} pour « ", $own->toArray($owner)['body']);
                $this->assertStringContainsString("votre demande de {$kind} pour « ", $sent->toSms($booking->user));
            }
        }
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
        $notification = BookingNotification::for($this->booking(), BookingStepEnum::RECEIVED);

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
        $mail = BookingNotification::for($booking, BookingStepEnum::RECEIVED)->toMail($booking->offer->user);
        $this->assertSame('Nouvelle demande : '.$booking->offer->getTranslation('title', 'fr'), $mail->subject);
        $this->assertStringContainsString('Marie Curie a envoyé une demande de séjour pour « ', $mail->introLines[0]);
        $this->assertSame('Message : Bonjour !', $mail->introLines[1]);
        $this->assertStringContainsString('« Réservations reçues »', $mail->introLines[2]);

        app()->setLocale('en');
        $this->assertStringContainsString('your stay request for', BookingNotification::for($booking, BookingStepEnum::ACCEPTED)->toSms($booking->user));
    }

    public function test_the_real_send_reaches_the_sms_sender_in_the_recipient_language(): void
    {
        // File synchrone en test : la notification part après l'enregistrement.
        $booking = $this->booking(owner: ['locale' => 'en', 'phone' => '+237690000009', 'phone_verified_at' => now()]);

        app(BookingService::class)->request($booking->user, $booking->offer, ['check_in' => today()->addDay()->toDateString(), 'check_out' => today()->addDays(2)->toDateString(), 'guests' => 1]);

        $this->assertCount(1, $this->sms->messages);
        $this->assertSame('+237690000009', $this->sms->messages[0]['to']);
        $this->assertStringContainsString('Marie Curie sent a stay request for', $this->sms->messages[0]['message']);
    }

    public function test_the_notification_keeps_the_status_at_send_time(): void
    {
        $booking = $this->booking();
        $notification = BookingNotification::for($booking, BookingStepEnum::RECEIVED);
        $booking->forceFill(['status' => BookingStatusEnum::ACCEPTED])->save();

        $data = unserialize(serialize($notification))->toArray($booking->offer->user);

        $this->assertSame('pending', $data['booking_status']);
        $this->assertSame('received', $data['step']);
        $this->assertSame('Nouvelle demande', $data['title']);
    }

    public function test_the_pill_is_the_real_booking_status_in_the_reader_language(): void
    {
        // Décision 2026-11-20-statut-reel.
        $owner = User::factory()->create(['locale' => 'en']);
        $booking = $this->booking();
        $create = fn (string $type, array $data) => $owner->notifications()->create(['id' => (string) Str::uuid(), 'type' => $type, 'data' => ['title' => 'Demande', 'body' => 'Texte', ...$data]]);
        // Reçue en attente, puis acceptée : la pastille suit la demande, pas l'étape.
        $create(BookingRequestReceived::class, ['step' => 'received', 'title' => 'Reçue', 'booking_id' => $booking->getKey()]);
        $booking->forceFill(['status' => BookingStatusEnum::ACCEPTED])->save();
        // Sans demande connue (ancienne notification) : le statut que nomme l'étape.
        $create(BookingRequestRefused::class, ['step' => 'refused', 'title' => 'Ancienne']);
        $create('Autre', ['title' => 'Autre', 'tag' => ['label' => 'Gardée', 'tone' => 'neutral']]);

        $tags = collect($this->withToken($owner->createToken('iPhone')->plainTextToken)->withHeaders(['Accept-Language' => 'en', 'X-Country' => 'CM'])
            ->getJson('/api/v1/notifications')->assertOk()->json('data.items'))->pluck('tag', 'title');

        $this->assertSame(['label' => 'Accepted', 'tone' => 'success'], $tags['Reçue']);
        $this->assertSame(['label' => 'Declined', 'tone' => 'neutral'], $tags['Ancienne']);
        $this->assertSame(['label' => 'Gardée', 'tone' => 'neutral'], $tags['Autre']);
    }

    public function test_an_accepted_answer_reaches_the_guest(): void
    {
        $booking = $this->booking(guest: ['phone' => '+237690000008', 'phone_verified_at' => now()]);

        app(BookingService::class)->answer($booking, true);

        $this->assertSame(BookingStatusEnum::ACCEPTED, $booking->fresh()->status);
        $this->assertSame('+237690000008', $this->sms->messages[0]['to']);
        $this->assertStringContainsString('a été acceptée', $this->sms->messages[0]['message']);
    }

    public function test_the_owner_own_answer_stays_in_the_tab(): void
    {
        // Décision 2026-11-20-reponse-dans-son-fil : ni e-mail ni SMS pour sa propre réponse.
        $booking = $this->booking(owner: ['phone' => '+237690000009', 'phone_verified_at' => now()]);
        $notification = BookingNotification::for($booking, BookingStepEnum::REFUSED, own: true);

        $this->assertSame(['database'], $notification->via($booking->offer->user));
        $this->assertTrue($notification->sentAsRead($booking->offer->user));
        $this->assertNull($notification->notificationUserId($booking->offer->user));

        app(BookingService::class)->answer($booking, false);

        // Seul le membre reçoit un SMS ; pas le propriétaire.
        $this->assertSame([], array_filter($this->sms->messages, fn (array $sms): bool => $sms['to'] === '+237690000009'));
        $this->assertStringContainsString('Vous avez refusé la demande de séjour de Marie Curie', $booking->offer->user->notifications()->sole()->data['body']);
    }

    public function test_a_request_lands_in_the_owner_notifications_tab_in_their_language(): void
    {
        // Décision 2026-11-19-contrat-centre-notifications : toujours, même sans e-mail ni SMS.
        $booking = $this->booking(owner: ['locale' => 'en', 'email_verified_at' => null]);
        $owner = $booking->offer->user;

        app(BookingService::class)->request($booking->user, $booking->offer, ['check_in' => today()->addDay()->toDateString(), 'check_out' => today()->addDays(2)->toDateString(), 'guests' => 1, 'message' => 'Bonjour, je viens pour le travail.']);

        $notification = $owner->notifications()->sole();
        // Le type de l'action (décision 2026-11-22-notification-par-action).
        $this->assertSame('New request', $notification->data['title']);
        $this->assertStringContainsString('Marie Curie sent a stay request for', $notification->data['body']);
        $this->assertSame('/offers/'.$booking->offer_id.'/calendar?month='.today()->addDay()->format('Y-m'), $notification->data['link']);
        $this->assertSame([], $notification->data['links']);
        $this->assertSame('Handle the request', $notification->data['link_label']);
        $this->assertSame('pending', $notification->data['booking_status']);
        $sent = Booking::query()->findOrFail($notification->data['booking_id']);
        $this->assertSame([$booking->offer_id, BookingStatusEnum::PENDING], [$sent->offer_id, $sent->status]);
        $this->assertSame($booking->user_id, (int) $notification->user_id);
        $this->assertArrayNotHasKey('actor_id', $notification->data);
        // « A stay request for “…” (from … to …, 1 person) has been recorded. », sans qui la fait (décision 2026-11-22-textes-carte-proprietaire).
        $this->assertStringStartsWith('A stay request for “'.$booking->offer->getTranslation('title', 'en').'” (from ', $notification->data['summary']);
        $this->assertStringEndsWith(', 1 person) has been recorded.', $notification->data['summary']);
        // Le total figé de la demande.
        $this->assertSame($booking->offer->bookings()->latest('id')->first()->formattedTotal('en'), $notification->data['total']);
        $this->assertSame($booking->offer_id, $notification->data['offer_id']);
        $this->assertSame([
            ['text' => 'Marie Curie', 'link' => '/members/'.$booking->user_id],
            ['text' => $booking->offer->getTranslation('title', 'en'), 'link' => '/offers/'.$booking->offer_id],
        ], $notification->data['text_links']);
        $this->assertSame('Bonjour, je viens pour le travail.', $notification->data['message']);
        $this->assertNull($notification->read_at);
    }
}
