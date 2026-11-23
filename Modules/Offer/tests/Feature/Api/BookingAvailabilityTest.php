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
use Modules\Offer\Enums\BookingStepEnum;
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
            'details' => ['property_type' => 'studio', 'rental_type' => ($furnished ? 'furnished' : 'long_term')],
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

        // La confirmation compte les demandes qui seront refusées.
        // Depuis le panneau du jour du calendrier, qui garde les boutons (décision 2026-11-22-demandes-carte-seule).
        $actions = fn (Booking $booking): array => collect($this->asUser($offer->user)->getJson("/api/v1/offers/{$offer->getKey()}/calendar/{$booking->check_in->toDateString()}")->json('data.items'))->firstWhere('id', (string) $booking->getKey())['actions'];
        $confirm = $actions($chosen)[0]['confirm'];
        // Le nombre seul, sans les noms (décision 2026-11-22-accepter-nombre-chevauchements).
        $this->assertStringEndsWith(' ? 1 autre demande chevauche ces dates et sera refusée.', $confirm);
        $this->assertStringNotContainsString((string) $overlapping->user->full_name, $confirm);
        $this->assertStringNotContainsString($after->user->full_name, $confirm);
        $this->assertStringNotContainsString('chevauche', $actions($after)[0]['confirm']);

        $this->asUser($offer->user)->postJson("/api/v1/bookings/{$chosen->getKey()}/accept")->assertNoContent();

        $this->assertSame(BookingStatusEnum::ACCEPTED, $chosen->fresh()->status);
        $this->assertSame(BookingStatusEnum::REFUSED, $overlapping->fresh()->status);
        $this->assertSame('These dates are no longer available.', $overlapping->fresh()->refusal_message);
        $this->assertSame(BookingStatusEnum::PENDING, $after->fresh()->status);
        Notification::assertSentTo($chosen->user, BookingRequestAccepted::class, fn (BookingRequestAccepted $notification): bool => $notification->step === BookingStepEnum::ACCEPTED);
        Notification::assertSentTo($overlapping->user, BookingRequestRefused::class, fn (BookingRequestRefused $notification): bool => $notification->step === BookingStepEnum::REFUSED);
        Notification::assertNotSentTo($after->user, BookingRequestRefused::class, fn (BookingRequestRefused $notification): bool => $notification->step === BookingStepEnum::REFUSED);
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

    public function test_the_booking_form_greys_out_taken_and_blocked_days(): void
    {
        $offer = $this->home();
        $this->stay($offer, 5, 7, BookingStatusEnum::ACCEPTED);
        $this->stay($offer, 9, 11);
        $this->block($offer, 12);
        $day = fn (int $days): string => today()->addDays($days)->toDateString();

        $fields = collect($this->asUser($this->member())->getJson('/api/v1/forms/offer.booking?record='.$offer->getKey())->assertOk()->json('data.fields'))->keyBy('name');
        $this->assertSame(['unavailable' => [$day(5), $day(6), $day(12)], 'after' => null], $fields['check_in']['calendar']);
        $this->assertSame(['unavailable' => [$day(5), $day(6), $day(12)], 'after' => 'check_in'], $fields['check_out']['calendar']);
        $this->assertNull($fields['guests']['calendar']);

        $visit = $this->home(false);
        $this->block($visit, 3);
        $this->assertSame(['unavailable' => [$day(3)], 'after' => null], $this->asUser($this->member())->getJson('/api/v1/forms/offer.booking?record='.$visit->getKey())->json('data.fields.0.calendar'));
    }

    public function test_a_visit_can_propose_a_second_date(): void
    {
        $offer = $this->home(false);
        $this->block($offer, 9);
        $url = "/api/v1/offers/{$offer->getKey()}/bookings";
        $date = fn (int $days): string => today()->addDays($days)->toDateString();

        // Une seconde date : gardée dans `check_out`, sans durée ni voyageurs ; écrite dans le texte.
        $this->asUser($this->member())->postJson($url, ['check_in' => $date(5), 'check_out' => $date(7)])->assertNoContent();
        $booking = $offer->bookings()->latest('id')->first();
        $this->assertSame([$date(5), $date(7)], [$booking->check_in->toDateString(), $booking->check_out->toDateString()]);
        $this->assertNull($booking->days());
        $this->assertNull($booking->guests);
        $this->assertMatchesRegularExpression('/^le .+ ou \d.+$/', $booking->period('fr'));

        // Facultative ; jamais la même date ; jamais un jour bloqué.
        $this->asUser($this->member())->postJson($url, ['check_in' => $date(6)])->assertNoContent();
        $this->asUser($this->member())->postJson($url, ['check_in' => $date(8), 'check_out' => $date(8)])->assertJsonValidationErrors('check_out');
        $this->asUser($this->member())->postJson($url, ['check_in' => $date(8), 'check_out' => $date(9)])->assertUnprocessable();

        // Le calendrier colore les deux jours de la visite.
        $days = collect($this->asUser($offer->user)->getJson("/api/v1/offers/{$offer->getKey()}/calendar?month=".today()->addDays(7)->format('Y-m'))->json('data.days'))->keyBy('date');
        $this->assertSame('pending', $days[$date(7)]['state']);
    }

    public function test_a_past_pending_request_expires_and_a_past_accepted_one_is_completed(): void
    {
        // Décision 2026-11-22-statuts-terminee-expiree.
        $offer = $this->home();
        $expired = $this->stay($offer, -3, -1);
        $today = $this->stay($offer, 0, 2);
        $completed = $this->stay($offer, -5, -2, BookingStatusEnum::ACCEPTED);
        $ongoing = $this->stay($offer, -1, 2, BookingStatusEnum::ACCEPTED);
        $visit = Booking::factory()->for($this->home(false))->for($this->member())->create(['type' => 'visit', 'check_in' => today()->subDays(2), 'check_out' => today()->addDay(), 'guests' => null]);

        $this->assertTrue($expired->isExpired());
        $this->assertFalse($expired->isAwaitingAnswer());
        $this->assertFalse($expired->isCancellable());
        $this->assertSame(['label' => 'Expirée', 'tone' => 'neutral'], $expired->statusTag());
        // L'arrivée aujourd'hui : encore en attente.
        $this->assertFalse($today->isExpired());
        $this->assertSame(['label' => 'En attente', 'tone' => 'warning'], $today->statusTag());
        $this->assertSame(['label' => 'Terminée', 'tone' => 'neutral'], $completed->statusTag());
        $this->assertSame(['label' => 'Acceptée', 'tone' => 'success'], $ongoing->statusTag());
        // Une visite avec une seconde date à venir n'expire pas.
        $this->assertFalse($visit->isExpired());
        $this->assertSame([(string) $today->getKey()], Booking::query()->whereKey([$expired->getKey(), $today->getKey()])->awaitingAnswer()->pluck('id')->map(fn ($id): string => (string) $id)->all());

        // Plus de réponse, de modification ni d'annulation : « Cette demande a expiré ».
        $this->asUser($offer->user)->postJson("/api/v1/bookings/{$expired->getKey()}/answer", ['answer' => 'accept'])
            ->assertForbidden()->assertJsonPath('message', 'Cette demande a expiré : sa date est passée.');
        $this->asUser($expired->user)->postJson("/api/v1/bookings/{$expired->getKey()}/cancel")->assertForbidden();

        // La pastille dans « Mes réservations » ; plus dans le filtre « En attente ».
        $items = collect($this->asUser($expired->user)->getJson('/api/v1/lists/offer.bookings')->json('data.items'))->keyBy('id');
        $this->assertSame('Expirée', $items[(string) $expired->getKey()]['tag']['label']);
        $this->assertSame([], $this->asUser($expired->user)->getJson('/api/v1/lists/offer.bookings?filter[status]=pending')->json('data.items'));
        $this->assertSame('Terminée', collect($this->asUser($completed->user)->getJson('/api/v1/lists/offer.bookings')->json('data.items'))->first()['tag']['label']);

        // Le compteur des demandes en attente de « Mes logements » ne la compte plus.
        $card = collect($this->asUser($offer->user)->getJson('/api/v1/lists/offer.mine')->json('data.items'))->firstWhere('id', (string) $offer->getKey());
        $this->assertStringStartsWith('1 ', $card['counter']['label']);
    }
}
