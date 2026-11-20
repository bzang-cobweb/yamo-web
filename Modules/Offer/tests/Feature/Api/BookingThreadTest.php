<?php

namespace Modules\Offer\Tests\Feature\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use Modules\Auth\Models\User;
use Modules\Auth\Sms\SmsSender;
use Modules\Auth\Tests\Concerns\FakeSmsSender;
use Modules\Location\Models\Country;
use Modules\Offer\Database\Seeders\OfferDatabaseSeeder;
use Modules\Offer\Models\Booking;
use Modules\Offer\Models\Offer;
use Modules\Offer\Services\BookingService;
use Tests\TestCase;

/**
 * Le suivi d'une demande (décision 2026-11-20-suivi-demande) : une carte par demande,
 * sa dernière étape ; sa page montre tout le suivi, des deux côtés, de la plus ancienne
 * étape à la plus récente ; ses non lues comptent pour une.
 */
class BookingThreadTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private User $guest;

    private Offer $offer;

    protected function setUp(): void
    {
        parent::setUp();

        config(['offer.domain' => 'rental']);
        Country::factory()->create(['code' => 'CM', 'currency' => 'XAF']);
        $this->seed(OfferDatabaseSeeder::class);
        $this->app->instance(SmsSender::class, new FakeSmsSender);
        $this->owner = User::factory()->create(['locale' => 'fr']);
        $this->guest = User::factory()->create(['locale' => 'fr']);
        $this->offer = Offer::factory()->for($this->owner, 'user')->create([
            'status' => 'published', 'price' => '45000', 'currency' => 'XAF',
            'details' => ['property_type' => 'studio', 'rental_type' => 'furnished'],
        ]);
    }

    private function request(int $from, string $message = 'Bonjour !'): Booking
    {
        return app(BookingService::class)->request($this->guest, $this->offer, [
            'check_in' => today()->addDays($from)->toDateString(), 'check_out' => today()->addDays($from + 2)->toDateString(), 'guests' => 1, 'message' => $message,
        ]);
    }

    private function api(User $user, string $method, string $uri): TestResponse
    {
        $this->app['auth']->forgetGuards();

        return $this->withToken($user->createToken('iPhone')->plainTextToken)->withHeaders(['X-Country' => 'CM', 'Accept-Language' => 'fr'])->json($method, '/api/v1/notifications'.$uri);
    }

    /** @return list<array<string, mixed>> */
    private function cards(User $user): array
    {
        return $this->api($user, 'GET', '')->assertOk()->json('data.items');
    }

    public function test_one_card_per_request_and_the_whole_follow_up_on_both_sides(): void
    {
        $this->travelTo(now()->setTime(9, 0));
        $booking = $this->request(3);
        $this->travel(5)->minutes();
        app(BookingService::class)->answer($booking, true);
        $this->travel(5)->minutes();
        app(BookingService::class)->cancel($booking->refresh());

        // Le propriétaire : demande reçue puis annulée, une carte, la dernière étape.
        $owner = $this->cards($this->owner);
        $this->assertCount(1, $owner);
        $this->assertSame(['label' => 'Annulée', 'tone' => 'neutral'], $owner[0]['tag']);
        // Le membre : une carte, l'acceptation.
        $this->assertCount(1, $this->cards($this->guest));

        $expected = ['Demande envoyée', 'Demande acceptée', 'Demande annulée'];
        foreach ([[$this->owner, $owner[0]['id']], [$this->guest, $this->cards($this->guest)[0]['id']]] as [$reader, $id]) {
            $history = $this->api($reader, 'GET', '/'.$id)->assertOk()->json('data.history');
            // Les deux côtés, de la plus ancienne étape à la plus récente.
            $this->assertSame($expected, array_column($history, 'label'));
            $this->assertSame([(string) $this->guest->getKey(), (string) $this->owner->getKey(), (string) $this->guest->getKey()], array_column(array_column($history, 'actor'), 'id'));
            $this->assertSame(['Bonjour !', null, null], array_column($history, 'message'));
            $this->assertSame(array_column($history, 'created_at'), collect($history)->pluck('created_at')->sort()->values()->all());
        }
    }

    public function test_a_refusal_shows_its_reason_in_the_follow_up(): void
    {
        $this->travelTo(now()->setTime(9, 0));
        $booking = $this->request(3);
        $this->travel(5)->minutes();
        app(BookingService::class)->answer($booking, false, 'Déjà réservé');

        $history = $this->api($this->owner, 'GET', '/'.$this->cards($this->owner)[0]['id'])->assertOk()->json('data.history');

        $this->assertSame(['Demande envoyée', 'Demande refusée'], array_column($history, 'label'));
        $this->assertSame('Déjà réservé', $history[1]['message']);
    }

    public function test_a_new_step_moves_its_request_to_the_top(): void
    {
        $this->travelTo(now()->setTime(9, 0));
        $first = $this->request(3, 'Première');
        $this->travel(5)->minutes();
        $this->request(10, 'Seconde');
        $this->assertSame(['Seconde', 'Première'], array_column($this->cards($this->owner), 'message'));

        $this->travel(5)->minutes();
        app(BookingService::class)->cancel($first->refresh());

        $cards = $this->cards($this->owner);
        $this->assertCount(2, $cards);
        // L'annulation de la première la remonte en tête.
        $this->assertSame(['Annulée', 'En attente'], array_column(array_column($cards, 'tag'), 'label'));
    }

    public function test_the_owner_answer_lands_in_their_own_card_already_read(): void
    {
        // Décision 2026-11-20-reponse-dans-son-fil.
        $this->travelTo(now()->setTime(9, 0));
        $first = $this->request(3, 'Première');
        $this->travel(5)->minutes();
        $this->request(10, 'Seconde');
        $this->travel(5)->minutes();
        app(BookingService::class)->answer($first, true);

        // Sa réponse remonte la première demande en tête, dans ses mots, sans qui agit.
        $cards = $this->cards($this->owner);
        $this->assertCount(2, $cards);
        $this->assertStringStartsWith('Vous avez accepté la demande de '.$this->guest->full_name, $cards[0]['body']);
        $this->assertNull($cards[0]['actor']);
        $this->assertNull($cards[0]['summary']);
        $this->assertSame(['label' => 'Acceptée', 'tone' => 'success'], $cards[0]['tag']);
        $this->assertSame('/offers/'.$this->offer->getKey().'/calendar?month='.$first->check_in->format('Y-m'), $cards[0]['link']);
        // Déjà lue : les non lues restent les deux demandes reçues.
        $this->assertNotNull($this->owner->notifications()->where('data->own', true)->sole()->read_at);
        $this->assertSame(2, $this->api($this->owner, 'GET', '/unread-count')->json('data.unread'));
        // Le suivi ne la répète pas : l'étape vient du membre.
        $history = $this->api($this->owner, 'GET', '/'.$cards[0]['id'])->assertOk()->json('data.history');
        $this->assertSame(['Demande envoyée', 'Demande acceptée'], array_column($history, 'label'));
        $this->assertSame((string) $this->owner->getKey(), $history[1]['actor']['id']);
    }

    public function test_the_page_shows_the_first_step_title_status_summary_and_price(): void
    {
        // Décision 2026-11-20-entete-premiere-etape : sur les deux côtés, quelle que soit l'étape ouverte.
        $this->travelTo(now()->setTime(9, 0));
        $booking = $this->request(3);
        $this->travel(5)->minutes();
        app(BookingService::class)->answer($booking, false, 'Déjà réservé');

        foreach ([$this->owner, $this->guest] as $reader) {
            $page = $this->api($reader, 'GET', '/'.$this->cards($reader)[0]['id'])->assertOk()->json('data');

            $this->assertSame('Demande de logement', $page['title']);
            $this->assertSame(['label' => 'Refusée', 'tone' => 'neutral'], $page['tag']);
            $this->assertStringStartsWith('Séjour du ', $page['summary']);
            $this->assertSame((string) $this->guest->getKey(), $page['actor']['id']);
            $this->assertSame($booking->formattedTotal('fr'), $page['total']);
            $this->assertNotNull($page['total']);
        }
    }

    public function test_the_reader_steps_say_whether_the_other_party_got_and_read_them(): void
    {
        // Décision 2026-11-20-coches-lecture.
        $this->travelTo(now()->setTime(9, 0));
        $booking = $this->request(3);
        $this->guest->forceFill(['last_seen_at' => null])->save();
        $this->travel(5)->minutes();
        app(BookingService::class)->answer($booking, true);
        $delivery = fn (): array => array_column($this->api($this->owner, 'GET', '/'.$this->cards($this->owner)[0]['id'])->assertOk()->json('data.history'), 'delivery');

        // La demande est du membre : rien ; la réponse du propriétaire : envoyée.
        $this->assertSame([null, 'sent'], $delivery());

        // Le membre s'est connecté depuis, sans lire : remise.
        $this->travel(5)->minutes();
        $this->guest->forceFill(['last_seen_at' => now()])->save();
        $this->assertSame([null, 'delivered'], $delivery());

        // Le membre l'a lue.
        $this->guest->notifications()->update(['read_at' => now()]);
        $this->assertSame([null, 'read'], $delivery());
    }

    public function test_unread_count_by_request_and_opening_reads_the_whole_request(): void
    {
        $this->travelTo(now()->setTime(9, 0));
        $booking = $this->request(3);
        $this->travel(5)->minutes();
        app(BookingService::class)->cancel($booking->refresh());
        // Une notification sans demande reste seule.
        $this->owner->notifications()->create(['id' => (string) Str::uuid(), 'type' => 'Autre', 'data' => ['title' => 'Autre', 'body' => 'Texte']]);

        $this->assertSame(2, $this->owner->unreadNotifications()->where('thread', 'booking:'.$booking->getKey())->count());
        $this->assertSame(2, $this->api($this->owner, 'GET', '/unread-count')->assertOk()->json('data.unread'));
        $cards = collect($this->cards($this->owner))->keyBy('title');
        $this->assertCount(2, $cards);
        $this->assertFalse($cards['Demande de logement']['read']);

        $this->api($this->owner, 'POST', '/'.$cards['Demande de logement']['id'].'/read')->assertNoContent();

        $this->assertSame(1, $this->api($this->owner, 'GET', '/unread-count')->json('data.unread'));
        $this->assertTrue(collect($this->cards($this->owner))->keyBy('title')['Demande de logement']['read']);
    }

    public function test_a_stranger_gets_no_follow_up(): void
    {
        $this->request(3);
        $id = $this->cards($this->owner)[0]['id'];

        $this->api(User::factory()->create(), 'GET', '/'.$id)->assertNotFound();
    }
}
