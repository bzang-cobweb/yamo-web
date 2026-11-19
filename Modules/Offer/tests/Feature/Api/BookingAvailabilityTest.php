<?php

namespace Modules\Offer\Tests\Feature\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Testing\TestResponse;
use Modules\Auth\Enums\RoleEnum;
use Modules\Auth\Models\Role;
use Modules\Auth\Models\User;
use Modules\Location\Models\Country;
use Modules\Offer\Database\Seeders\OfferDatabaseSeeder;
use Modules\Offer\Enums\BookingStatusEnum;
use Modules\Offer\Models\Booking;
use Modules\Offer\Models\Offer;
use Modules\Offer\Notifications\BookingRequestAccepted;
use Modules\Offer\Notifications\BookingRequestRefused;
use Tests\TestCase;

/**
 * Disponibilité d'un logement (décision 2026-11-19-contrat-calendrier-offre) : jamais
 * deux séjours acceptés qui se chevauchent, ni un séjour sur un jour bloqué ;
 * accepter refuse les demandes en attente qui chevauchent.
 */
class BookingAvailabilityTest extends TestCase
{
    use RefreshDatabase;

    private int $phones = 0;

    protected function setUp(): void
    {
        parent::setUp();

        config(['offer.domain' => 'rental']);
        Country::factory()->create(['code' => 'CM', 'currency' => 'XAF']);
        $this->seed(OfferDatabaseSeeder::class);
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
        $this->app['auth']->forgetGuards();

        return $this->withToken($user->createToken('iPhone')->plainTextToken)->withHeaders(['Accept-Language' => 'fr', 'X-Country' => 'CM']);
    }

    private function home(bool $furnished = true): Offer
    {
        $owner = $this->member(['phone' => '+23769000010'.(++$this->phones), 'phone_verified_at' => now()]);

        return Offer::factory()->for($owner, 'user')->create([
            'status' => 'published', 'price' => '45000', 'currency' => 'XAF',
            'details' => ['property_type' => 'studio', 'furnished' => $furnished],
        ]);
    }

    /** Un séjour du jour `$in` au jour `$out` (comptés depuis aujourd'hui). */
    private function stay(Offer $offer, int $in, int $out, BookingStatusEnum $status = BookingStatusEnum::PENDING, array $guest = []): Booking
    {
        return Booking::factory()->for($offer)->for($this->member($guest))->status($status)->create([
            'check_in' => today()->addDays($in), 'check_out' => today()->addDays($out),
        ]);
    }

    private function request(Offer $offer, int $in, ?int $out = null): TestResponse
    {
        $data = ['check_in' => today()->addDays($in)->toDateString(), 'message' => null];
        if ($out !== null) {
            $data += ['check_out' => today()->addDays($out)->toDateString(), 'guests' => '1'];
        }

        return $this->asUser($this->member())->postJson("/api/v1/offers/{$offer->getKey()}/bookings", $data);
    }

    private function block(Offer $offer, int $day): void
    {
        $offer->blockedDates()->create(['date' => today()->addDays($day)]);
    }

    public function test_a_request_cannot_take_the_nights_of_an_accepted_stay_or_a_blocked_day(): void
    {
        $offer = $this->home();
        $this->stay($offer, 5, 8, BookingStatusEnum::ACCEPTED);
        $this->block($offer, 12);

        $this->request($offer, 7, 10)->assertUnprocessable()->assertJsonPath('errors.check_in.0', "Ces dates ne sont pas disponibles : choisissez d'autres jours.");
        $this->request($offer, 2, 6)->assertUnprocessable();
        $this->request($offer, 11, 13)->assertUnprocessable();
        // Le jour du départ est libre pour une arrivée ; la veille d'un jour bloqué aussi.
        $this->request($offer, 8, 10)->assertNoContent();
        $this->request($offer, 2, 5)->assertNoContent();
        $this->request($offer, 10, 12)->assertNoContent();
        // Une demande en attente ne bloque personne.
        $this->request($offer, 9, 11)->assertNoContent();
    }

    public function test_a_visit_is_only_stopped_by_a_blocked_day(): void
    {
        $offer = $this->home(false);
        $this->block($offer, 4);

        $this->request($offer, 4)->assertUnprocessable()->assertJsonValidationErrors('check_in');
        $this->request($offer, 5)->assertNoContent();
        $this->request($offer, 5)->assertNoContent();
    }

    public function test_accepting_declines_the_overlapping_pending_requests_and_tells_their_members(): void
    {
        $offer = $this->home();
        $chosen = $this->stay($offer, 5, 8);
        $overlapping = $this->stay($offer, 6, 8, guest: ['locale' => 'en']);
        $after = $this->stay($offer, 8, 10);

        // La confirmation nomme la demande qui sera refusée.
        $items = collect($this->asUser($offer->user)->getJson('/api/v1/lists/offer.bookings.received')->json('data.items'))->keyBy('id');
        $confirm = $items[(string) $chosen->getKey()]['actions'][0]['confirm'];
        $this->assertStringContainsString('Cette demande en attente chevauche ces dates et sera refusée : '.$overlapping->user->full_name, $confirm);
        $this->assertStringNotContainsString($after->user->full_name, $confirm);
        $this->assertStringNotContainsString('chevauche', $items[(string) $after->getKey()]['actions'][0]['confirm']);

        $this->asUser($offer->user)->postJson("/api/v1/bookings/{$chosen->getKey()}/accept")->assertNoContent();

        $this->assertSame(BookingStatusEnum::ACCEPTED, $chosen->fresh()->status);
        $this->assertSame(BookingStatusEnum::REFUSED, $overlapping->fresh()->status);
        $this->assertSame('These dates are no longer available.', $overlapping->fresh()->refusal_message);
        $this->assertSame(BookingStatusEnum::PENDING, $after->fresh()->status);
        Notification::assertSentTo($chosen->user, BookingRequestAccepted::class);
        Notification::assertSentTo($overlapping->user, BookingRequestRefused::class);
        Notification::assertNotSentTo($after->user, BookingRequestRefused::class);
    }

    public function test_a_request_overlapping_an_accepted_stay_or_a_blocked_day_cannot_be_accepted(): void
    {
        $offer = $this->home();
        $this->stay($offer, 5, 8, BookingStatusEnum::ACCEPTED);
        $overlapping = $this->stay($offer, 6, 9);
        $blocked = $this->stay($offer, 20, 22);
        $this->block($offer, 21);

        foreach ([$overlapping, $blocked] as $booking) {
            $this->asUser($offer->user)->postJson("/api/v1/bookings/{$booking->getKey()}/accept")
                ->assertUnprocessable()
                ->assertJsonPath('errors.answer.0', 'Ces dates chevauchent un séjour accepté ou un jour bloqué : la demande ne peut pas être acceptée.');
            $this->assertSame(BookingStatusEnum::PENDING, $booking->fresh()->status);
        }

        // Par le formulaire de réponse aussi ; refuser reste possible.
        $this->asUser($offer->user)->postJson("/api/v1/bookings/{$overlapping->getKey()}/answer", ['answer' => 'accept'])->assertUnprocessable();
        $this->asUser($offer->user)->postJson("/api/v1/bookings/{$overlapping->getKey()}/answer", ['answer' => 'refuse'])->assertNoContent();
        $this->assertSame(BookingStatusEnum::REFUSED, $overlapping->fresh()->status);
    }
}
