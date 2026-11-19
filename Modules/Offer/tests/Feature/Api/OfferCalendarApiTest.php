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
 * Le calendrier d'un logement, pour son propriétaire (décision
 * 2026-11-19-contrat-calendrier-offre) : le mois, le panneau d'un jour, bloquer et
 * débloquer, le bouton de la carte de « Mes logements ».
 */
class OfferCalendarApiTest extends TestCase
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

    /** Le mois de `today() + $days`, au format `AAAA-MM`. */
    private function monthOf(int $days): string
    {
        return today()->addDays($days)->format('Y-m');
    }

    /** @return array<string, array{date: string, day: int, state: string, dot: string|null}> */
    private function days(Offer $offer, string $month): array
    {
        return collect($this->asUser($offer->user)->getJson("/api/v1/offers/{$offer->getKey()}/calendar?month={$month}")->assertOk()->json('data.days'))->keyBy('date')->all();
    }

    private function date(int $days): string
    {
        return today()->addDays($days)->toDateString();
    }

    public function test_only_the_owner_sees_the_calendar_and_the_home_card_opens_it(): void
    {
        $offer = $this->home();

        $this->withHeaders(['X-Country' => 'CM'])->getJson("/api/v1/offers/{$offer->getKey()}/calendar")->assertUnauthorized();
        $this->asUser($this->member())->getJson("/api/v1/offers/{$offer->getKey()}/calendar")->assertNotFound();
        $this->asUser($this->member())->putJson("/api/v1/offers/{$offer->getKey()}/blocked-dates/{$this->date(3)}")->assertNotFound();

        $data = $this->asUser($offer->user)->getJson("/api/v1/offers/{$offer->getKey()}/calendar")->assertOk()->json('data');
        $this->assertSame(today()->format('Y-m'), $data['month']);
        $this->assertStringStartsWith('Calendrier · ', $data['title']);
        $this->assertSame(today()->daysInMonth, count($data['days']));
        $this->assertSame(today()->startOfMonth()->dayOfWeekIso - 1, $data['offset']);
        $this->assertCount(7, $data['weekdays']);
        $this->assertSame(['accepted', 'pending', 'conflict', 'blocked'], array_column($data['legend'], 'tone'));
        $this->assertSame(today()->subMonth()->format('Y-m'), $data['previous']);
        $this->assertSame(today()->addMonthNoOverflow()->format('Y-m'), $data['next']);

        $card = collect($this->asUser($offer->user)->getJson('/api/v1/lists/offer.mine')->json('data.items'))->firstWhere('id', (string) $offer->getKey());
        $this->assertSame(['label' => 'Calendrier', 'style' => 'default', 'confirm' => null, 'submission' => null, 'form' => null, 'link' => "/offers/{$offer->getKey()}/calendar"], $card['actions'][0]);
    }

    public function test_each_day_shows_accepted_pending_overlap_blocked_past_and_free(): void
    {
        $offer = $this->home();
        $this->stay($offer, 40, 43, BookingStatusEnum::ACCEPTED);
        $this->stay($offer, 42, 45);
        $this->stay($offer, 50, 51, BookingStatusEnum::REFUSED);
        $offer->blockedDates()->create(['date' => today()->addDays(47)]);
        $this->stay($offer, 47, 48);

        $month = $this->monthOf(40);
        $days = $this->days($offer, $month) + $this->days($offer, $this->monthOf(51));

        $this->assertSame('accepted', $days[$this->date(40)]['state']);
        $this->assertSame('conflict', $days[$this->date(42)]['state']);
        // Le jour du départ n'est pas une nuit occupée.
        $this->assertSame('pending', $days[$this->date(43)]['state']);
        $this->assertSame('free', $days[$this->date(45)]['state']);
        $this->assertSame('conflict', $days[$this->date(47)]['state']);
        $this->assertSame('free', $days[$this->date(50)]['state']);
        $this->assertSame('past', $this->days($offer, today()->subMonth()->format('Y-m'))[today()->subMonth()->startOfMonth()->toDateString()]['state']);
    }

    public function test_visits_are_a_dot_on_their_day(): void
    {
        $offer = $this->home(false);
        Booking::factory()->for($offer)->for($this->member())->visit()->create(['check_in' => today()->addDays(40)]);
        Booking::factory()->for($offer)->for($this->member())->visit()->status(BookingStatusEnum::ACCEPTED)->create(['check_in' => today()->addDays(41)]);

        $data = $this->asUser($offer->user)->getJson("/api/v1/offers/{$offer->getKey()}/calendar?month={$this->monthOf(40)}")->json('data');
        $days = collect($data['days'])->keyBy('date');
        $this->assertSame(['state' => 'free', 'dot' => 'warning'], array_intersect_key($days[$this->date(40)], ['state' => 1, 'dot' => 1]));
        $this->assertSame('success', $days[$this->date(41)]['dot']);
        $this->assertSame(['visit_accepted' => 'success', 'visit_pending' => 'warning'], [
            'visit_accepted' => $data['legend'][0]['tone'], 'visit_pending' => $data['legend'][1]['tone'],
        ]);
    }

    public function test_the_day_panel_lists_its_requests_with_their_buttons_and_blocks_or_unblocks(): void
    {
        $offer = $this->home();
        $pending = $this->stay($offer, 40, 43);
        $this->stay($offer, 50, 52, BookingStatusEnum::ACCEPTED);

        $day = $this->asUser($offer->user)->getJson("/api/v1/offers/{$offer->getKey()}/calendar/{$this->date(41)}")->assertOk()->json('data');
        $this->assertSame([(string) $pending->getKey()], array_column($day['items'], 'id'));
        $this->assertSame(['Accepter', 'Refuser'], array_column($day['items'][0]['actions'], 'label'));
        $this->assertSame('Bloquer ce jour', $day['action']['label']);
        $this->assertSame(['method' => 'PUT', 'endpoint' => "/v1/offers/{$offer->getKey()}/blocked-dates/{$this->date(41)}"], array_intersect_key($day['action']['submission'], ['method' => 1, 'endpoint' => 1]));

        // Un jour libre ; un jour pris par un séjour accepté ne se bloque pas.
        $free = $this->asUser($offer->user)->getJson("/api/v1/offers/{$offer->getKey()}/calendar/{$this->date(30)}")->json('data');
        $this->assertSame([], $free['items']);
        $this->assertSame('Aucune demande ce jour-là.', $free['empty']);
        $taken = $this->asUser($offer->user)->getJson("/api/v1/offers/{$offer->getKey()}/calendar/{$this->date(50)}")->json('data');
        $this->assertNull($taken['action']);
        $this->assertSame('Un séjour accepté occupe ce jour : il ne peut pas être bloqué.', $taken['note']);
        $this->asUser($offer->user)->putJson("/api/v1/offers/{$offer->getKey()}/blocked-dates/{$this->date(50)}")->assertUnprocessable()->assertJsonValidationErrors('date');
        $this->asUser($offer->user)->putJson("/api/v1/offers/{$offer->getKey()}/blocked-dates/{$this->date(-1)}")->assertUnprocessable();

        // Bloquer, deux fois sans doublon ; puis débloquer.
        $this->asUser($offer->user)->putJson("/api/v1/offers/{$offer->getKey()}/blocked-dates/{$this->date(30)}")->assertNoContent();
        $this->asUser($offer->user)->putJson("/api/v1/offers/{$offer->getKey()}/blocked-dates/{$this->date(30)}")->assertNoContent();
        $this->assertSame(1, $offer->blockedDates()->count());
        $this->assertSame('blocked', $this->days($offer, $this->monthOf(30))[$this->date(30)]['state']);
        $this->assertSame('Débloquer', $this->asUser($offer->user)->getJson("/api/v1/offers/{$offer->getKey()}/calendar/{$this->date(30)}")->json('data.action.label'));
        $this->asUser($offer->user)->deleteJson("/api/v1/offers/{$offer->getKey()}/blocked-dates/{$this->date(30)}")->assertNoContent();
        $this->assertSame(0, $offer->blockedDates()->count());
    }
}
