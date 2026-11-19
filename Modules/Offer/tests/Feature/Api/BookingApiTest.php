<?php

namespace Modules\Offer\Tests\Feature\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Modules\Auth\Enums\RoleEnum;
use Modules\Auth\Models\Role;
use Modules\Auth\Models\User;
use Modules\Auth\Notifications\Channels\SmsChannel;
use Modules\Location\Models\Country;
use Modules\Offer\Database\Seeders\OfferDatabaseSeeder;
use Modules\Offer\Domains\DomainRegistry;
use Modules\Offer\Enums\BookingStatusEnum;
use Modules\Offer\Models\Booking;
use Modules\Offer\Models\Offer;
use Modules\Offer\Notifications\BookingRequestCancelled;
use Modules\Offer\Notifications\BookingRequestReceived;
use Modules\Offer\Notifications\BookingRequestRefused;
use Tests\TestCase;

/**
 * Réservations et visites (décision 2026-11-19-contrat-reservations) : le bouton de
 * la fiche, la demande, la réponse du propriétaire, l'annulation, les listes du
 * profil et les e-mails et SMS.
 */
class BookingApiTest extends TestCase
{
    use RefreshDatabase;

    private int $phones = 0;

    protected function setUp(): void
    {
        parent::setUp();

        config(['offer.domain' => 'rental']);
        Country::factory()->create(['code' => 'CM', 'currency' => 'XAF']);
        $this->seed(OfferDatabaseSeeder::class);
        // Qui est prévenu, de quoi, sur quels canaux ; l'envoi réel est testé dans BookingNotificationsTest.
        Notification::fake();
    }

    private function member(array $attributes = []): User
    {
        $user = User::factory()->create(['locale' => 'fr', 'email_verified_at' => now(), ...$attributes]);
        $user->assignRole(Role::findOrCreate(RoleEnum::MEMBER->value, 'web'));

        return $user;
    }

    private function asUser(User $user): static
    {
        // Un autre compte à chaque appel : le garde ne garde pas le précédent.
        $this->app['auth']->forgetGuards();

        return $this->withToken($user->createToken('iPhone')->plainTextToken)->withHeaders(['Accept-Language' => 'fr', 'X-Country' => 'CM']);
    }

    private function home(bool $furnished, ?User $owner = null): Offer
    {
        $owner ??= $this->member(['phone' => '+23769000010'.(++$this->phones), 'phone_verified_at' => now()]);

        return Offer::factory()->for($owner, 'user')->create([
            'status' => 'published', 'price' => '45000', 'currency' => 'XAF',
            'details' => ['property_type' => 'studio', 'furnished' => $furnished],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function stay(array $overrides = []): array
    {
        return ['check_in' => today()->addDays(5)->toDateString(), 'check_out' => today()->addDays(8)->toDateString(), 'guests' => '2', 'message' => 'Bonjour !', ...$overrides];
    }

    public function test_the_offer_page_serves_the_button_by_type_and_never_to_its_owner(): void
    {
        $furnished = $this->home(true);
        $unfurnished = $this->home(false);

        // Un visiteur le voit aussi : l'app le mène d'abord à la connexion.
        $this->withHeaders(['X-Country' => 'CM', 'Accept-Language' => 'fr'])->getJson("/api/v1/offers/{$furnished->getKey()}")
            ->assertJsonPath('data.booking', ['type' => 'stay', 'label' => 'Réserver', 'form' => 'offer.booking']);
        $this->asUser($this->member())->getJson("/api/v1/offers/{$unfurnished->getKey()}")
            ->assertJsonPath('data.booking', ['type' => 'visit', 'label' => 'Demander une visite', 'form' => 'offer.booking']);
        // Son auteur : pas de réservation, mais « Modifier » (décision 2026-11-19-fiche-bouton-modifier).
        $this->asUser($furnished->user)->getJson("/api/v1/offers/{$furnished->getKey()}")
            ->assertJsonPath('data.booking', null)
            ->assertJsonPath('data.edit', ['label' => 'Modifier', 'form' => 'offer.offer']);
        $this->asUser($this->member())->getJson("/api/v1/offers/{$furnished->getKey()}")->assertJsonPath('data.edit', null);
        $this->withHeaders(['X-Country' => 'CM'])->getJson("/api/v1/offers/{$unfurnished->getKey()}")->assertJsonPath('data.edit', null);
    }

    public function test_the_form_asks_dates_and_guests_for_a_stay_and_a_date_for_a_visit(): void
    {
        $guest = $this->member();
        $offer = $this->home(true);
        $stay = $this->asUser($guest)->getJson('/api/v1/forms/offer.booking?record='.$offer->getKey())->assertOk();
        $this->assertSame(['check_in', 'check_out', 'guests', 'message'], array_column($stay->json('data.fields'), 'name'));
        $stay->assertJsonPath('data.title', 'Réserver')
            // Le prix par jour, tel que la fiche l'affiche (espaces insécables compris).
            ->assertJsonPath('data.fields.1.help', app(DomainRegistry::class)->require()->formattedPrice($offer, 'fr').' ; le total figure dans « Mes réservations » une fois la demande envoyée.');

        // Le calendrier : à partir d'aujourd'hui (le départ, de demain), un an au plus (décision 2026-11-19-bornes-des-dates).
        $stay->assertJsonPath('data.fields.0.date_range', ['min' => today()->toDateString(), 'max' => today()->addDays(365)->toDateString()])
            ->assertJsonPath('data.fields.1.date_range.min', today()->addDay()->toDateString());

        $visit = $this->asUser($guest)->getJson('/api/v1/forms/offer.booking?record='.$this->home(false)->getKey())->assertOk();
        $this->assertSame(['check_in', 'message'], array_column($visit->json('data.fields'), 'name'));
        $visit->assertJsonPath('data.title', 'Demander une visite')->assertJsonPath('data.fields.0.label', 'Date souhaitée');
    }

    public function test_a_stay_request_freezes_the_price_and_tells_the_owner_by_mail_and_sms(): void
    {
        $offer = $this->home(true);
        $guest = $this->member(['first_name' => 'Marie', 'last_name' => 'Curie']);

        $this->asUser($guest)->postJson("/api/v1/offers/{$offer->getKey()}/bookings", $this->stay())->assertNoContent();

        $booking = Booking::query()->sole();
        $this->assertSame(BookingStatusEnum::PENDING, $booking->status);
        $this->assertSame(3, $booking->days());
        $this->assertSame('135000.00', $booking->total);
        $this->assertSame('XAF', $booking->currency);
        $this->assertSame(2, $booking->guests);
        $this->assertSame('Bonjour !', $booking->message);

        // Le propriétaire, par e-mail et SMS (adresse et numéro vérifiés).
        Notification::assertSentTo($offer->user, BookingRequestReceived::class,
            fn (BookingRequestReceived $notification, array $channels): bool => $notification->booking->is($booking) && $channels === ['database', 'mail', SmsChannel::class]);
        Notification::assertCount(1);
    }

    public function test_invalid_requests_are_refused(): void
    {
        $offer = $this->home(true);
        $guest = $this->member();
        $url = "/api/v1/offers/{$offer->getKey()}/bookings";

        $this->asUser($guest)->postJson($url, $this->stay(['check_in' => today()->subDay()->toDateString()]))->assertJsonValidationErrors('check_in');
        $this->asUser($guest)->postJson($url, $this->stay(['check_out' => today()->addDays(5)->toDateString()]))->assertJsonValidationErrors('check_out');
        $this->asUser($guest)->postJson($url, $this->stay(['check_out' => today()->addDays(400)->toDateString()]))->assertJsonValidationErrors('check_out');
        $this->asUser($guest)->postJson($url, $this->stay(['guests' => '0']))->assertJsonValidationErrors('guests');

        // Une seule demande en attente par offre.
        $this->asUser($guest)->postJson($url, $this->stay())->assertNoContent();
        $this->asUser($guest)->postJson($url, $this->stay())->assertJsonValidationErrors(['check_in' => 'Vous avez déjà une demande en attente pour cette offre.']);

        // Pas la sienne ; pas une offre non publiée.
        $this->asUser($offer->user)->postJson($url, $this->stay())->assertForbidden();
        $draft = $this->home(true);
        $draft->update(['status' => 'draft']);
        $this->asUser($guest)->postJson("/api/v1/offers/{$draft->getKey()}/bookings", $this->stay())->assertNotFound();
        $this->assertSame(1, Booking::query()->count());
    }

    public function test_a_visit_keeps_one_date_and_no_price(): void
    {
        $offer = $this->home(false);

        $this->asUser($this->member())->postJson("/api/v1/offers/{$offer->getKey()}/bookings", ['check_in' => today()->addDays(2)->toDateString()])->assertNoContent();

        $booking = Booking::query()->sole();
        $this->assertNull($booking->check_out);
        $this->assertNull($booking->guests);
        $this->assertNull($booking->total);
    }

    public function test_the_owner_answers_from_received_bookings_and_the_guest_is_told(): void
    {
        $offer = $this->home(true);
        $guest = $this->member(['phone' => '+237690000002', 'phone_verified_at' => now()]);
        $booking = Booking::factory()->for($offer)->for($guest)->create();
        $owner = $offer->user;

        // « Réservations reçues » n'est plus un lien du menu : elle s'ouvre depuis chaque logement
        // (décision 2026-11-19-contrat-reservations-logements-profil).
        $this->assertNotContains('offer.bookings.received', array_column($this->asUser($owner)->getJson('/api/v1/auth/me/menu')->json('data'), 'list'));
        $item = $this->asUser($owner)->getJson('/api/v1/lists/offer.bookings.received')->assertOk()->json('data.items.0');
        $this->assertSame(['form' => 'offer.booking.answer', 'record' => (string) $booking->getKey()], $item['edit']);
        $this->assertStringStartsWith('En attente · 135', (string) $item['highlight']);

        // Le formulaire : la demande à lire, puis la réponse.
        $fields = array_column($this->asUser($owner)->getJson('/api/v1/forms/offer.booking.answer?record='.$booking->getKey())->assertOk()->json('data.fields'), null, 'name');
        $this->assertTrue($fields['guest']['readonly']);
        $this->assertSame(['accept', 'refuse'], array_column($fields['answer']['options'], 'value'));

        // Un autre que le propriétaire : 404.
        $this->asUser($guest)->postJson("/api/v1/bookings/{$booking->getKey()}/answer", ['answer' => 'accept'])->assertNotFound();

        $this->asUser($owner)->postJson("/api/v1/bookings/{$booking->getKey()}/answer", ['answer' => 'refuse', 'refusal_message' => 'Déjà loué.'])->assertNoContent();
        $this->assertSame(BookingStatusEnum::REFUSED, $booking->fresh()->status);
        $this->assertSame('Déjà loué.', $booking->fresh()->refusal_message);
        Notification::assertSentTo($guest, BookingRequestRefused::class,
            fn (BookingRequestRefused $notification, array $channels): bool => $channels === ['database', 'mail', SmsChannel::class]);

        // Plus en attente : plus de réponse possible, plus de « Répondre ».
        $this->asUser($owner)->postJson("/api/v1/bookings/{$booking->getKey()}/answer", ['answer' => 'accept'])->assertForbidden();
        $this->assertNull($this->asUser($owner)->getJson('/api/v1/lists/offer.bookings.received')->json('data.items.0.edit'));
    }

    public function test_the_guest_follows_and_cancels_their_bookings(): void
    {
        $guest = $this->member();
        $pending = Booking::factory()->for($this->home(true), 'offer')->for($guest)->create();
        $started = Booking::factory()->for($this->home(true), 'offer')->for($guest)
            ->status(BookingStatusEnum::ACCEPTED)->create(['check_in' => today(), 'check_out' => today()->addDays(2)]);

        $items = collect($this->asUser($guest)->getJson('/api/v1/lists/offer.bookings')->assertOk()->json('data.items'))->keyBy('id');
        $this->assertSame('Annuler la demande', $items[(string) $pending->getKey()]['delete']['label']);
        $this->assertSame('/offers/'.$pending->offer_id, $items[(string) $pending->getKey()]['link']);
        // Acceptée et commencée : plus annulable.
        $this->assertNull($items[(string) $started->getKey()]['delete']);

        $cancel = $items[(string) $pending->getKey()]['delete'];
        $this->asUser($guest)->json($cancel['method'], '/api'.$cancel['endpoint'])->assertNoContent();
        $this->assertSame(BookingStatusEnum::CANCELLED, $pending->fresh()->status);
        Notification::assertSentTo($pending->offer->user, BookingRequestCancelled::class);

        $this->asUser($guest)->postJson("/api/v1/bookings/{$started->getKey()}/cancel")->assertForbidden();
        $this->asUser($this->member())->postJson("/api/v1/bookings/{$started->getKey()}/cancel")->assertNotFound();
    }

    public function test_no_mail_or_sms_to_an_unverified_address_or_number(): void
    {
        $owner = $this->member(['email_verified_at' => null, 'phone' => '+237690000003', 'phone_verified_at' => null]);
        $offer = $this->home(true, $owner);

        $this->asUser($this->member())->postJson("/api/v1/offers/{$offer->getKey()}/bookings", $this->stay())->assertNoContent();

        // Ni e-mail ni SMS : l'onglet « Notifications » seulement (décision 2026-11-19-contrat-centre-notifications).
        Notification::assertSentTo($owner, BookingRequestReceived::class, fn (BookingRequestReceived $notification, array $channels): bool => $channels === ['database']);
    }
}
