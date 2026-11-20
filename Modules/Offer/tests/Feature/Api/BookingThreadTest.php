<?php

namespace Modules\Offer\Tests\Feature\Api;

use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Number;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use Modules\Auth\Models\User;
use Modules\Auth\Sms\SmsSender;
use Modules\Auth\Tests\Concerns\FakeSmsSender;
use Modules\Location\Models\Country;
use Modules\Offer\Database\Seeders\OfferDatabaseSeeder;
use Modules\Offer\Http\Resources\BookingResource;
use Modules\Offer\Models\Booking;
use Modules\Offer\Models\Offer;
use Modules\Offer\Services\BookingService;
use Modules\Offer\Support\BookingText;
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
        // Le titre de la carte : le type de location (décision 2026-11-20-titre-carte).
        $this->assertSame('Location meublée', $owner[0]['card_title']);
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
            $this->assertSame('Demande de location « '.$this->offer->getTranslation('title', 'fr').' » '.$booking->period('fr').' pour 1 personne.', $page['summary']);
            $this->assertSame((string) $this->guest->getKey(), $page['actor']['id']);
            // La demande, ajoutée par le module Offer (décision 2026-11-20-notification-generique).
            $this->assertArrayNotHasKey('total', $page);
            $this->assertSame([
                'id' => (string) $booking->getKey(), 'offer_id' => (string) $this->offer->getKey(), 'type' => 'stay', 'status' => 'refused',
                'check_in' => $booking->check_in->toDateString(), 'check_out' => $booking->check_out?->toDateString(), 'guests' => 1,
                'offer' => ['title' => $this->offer->getTranslation('title', 'fr'), 'rental_type' => 'Location meublée'],
            ], array_diff_key($page['booking'], ['lines' => true]));
            // Les lignes, libellé et valeur, dans l'ordre ; sans chambres renseignées, pas de ligne (décision 2026-11-20-lignes-demande).
            $this->assertSame([
                ['key' => 'guests', 'label' => 'Personnes', 'value' => '1'],
                ['key' => 'arrival', 'label' => 'Arrivée', 'value' => $booking->check_in->locale('fr')->isoFormat('ll')],
                ['key' => 'departure', 'label' => 'Départ', 'value' => (string) $booking->check_out?->locale('fr')->isoFormat('ll')],
                ['key' => 'days', 'label' => 'Durée', 'value' => '2 jours'],
                ['key' => 'price', 'label' => 'Prix par jour', 'value' => Number::currency(45000, 'XAF', 'fr')],
                // La caution du meublé, toujours (décision 2026-11-20-caution-meuble-toujours).
                ['key' => 'deposit', 'label' => 'Caution', 'value' => Number::currency(0, 'XAF', 'fr')],
                ['key' => 'total', 'label' => 'Total', 'value' => (string) $booking->formattedTotal('fr')],
            ], $page['booking']['lines']);
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

    public function test_each_request_is_read_once_per_api_call(): void
    {
        // Décision 2026-11-20-cache-fil : une demande lue une fois, quel que soit le nombre de cartes ou d'usages.
        $this->travelTo(now()->setTime(9, 0));
        $this->request(3);
        $this->travel(5)->minutes();
        $second = $this->request(10);
        $this->travel(5)->minutes();
        app(BookingService::class)->answer($second, true);
        $count = function (string $uri): int {
            $queries = [];
            DB::listen(function (QueryExecuted $query) use (&$queries): void {
                if (str_contains($query->sql, 'from `bookings`') || str_contains($query->sql, 'from "bookings"')) {
                    $queries[] = $query->sql;
                }
            });
            $this->api($this->owner, 'GET', $uri)->assertOk();
            $this->app['events']->forget(QueryExecuted::class);

            return count($queries);
        };

        // La liste : toutes ses demandes en une requête.
        $this->assertSame(1, $count(''));
        // La page : la pastille et la demande, une seule lecture.
        $this->assertSame(1, $count('/'.$this->cards($this->owner)[0]['id']));
    }

    public function test_the_dates_of_a_stay_use_the_home_hours_and_a_visit_its_second_date(): void
    {
        // Décision 2026-11-20-heures-arrivee-depart.
        $stay = Booking::factory()->for($this->offer)->create(['check_in' => '2026-12-05', 'check_out' => '2026-12-07']);
        $this->assertSame([['key' => 'arrival', 'label' => 'Arrivée', 'value' => '5 déc. 2026'], ['key' => 'departure', 'label' => 'Départ', 'value' => '7 déc. 2026']], $stay->dateLines('fr'));

        $this->offer->update(['details' => [...$this->offer->details, 'check_in_from' => '14:00', 'check_out_until' => '11:00']]);
        $this->assertSame([['key' => 'arrival', 'label' => 'Arrivée', 'value' => '5 déc. 2026 à partir de 14:00'], ['key' => 'departure', 'label' => 'Départ', 'value' => '7 déc. 2026 au plus tard à 11:00']], $stay->refresh()->dateLines('fr'));
        $this->assertSame([['key' => 'arrival', 'label' => 'Arrival', 'value' => 'Dec 5, 2026 from 14:00'], ['key' => 'departure', 'label' => 'Departure', 'value' => 'Dec 7, 2026 by 11:00']], $stay->dateLines('en'));

        $visit = Booking::factory()->for($this->offer)->create(['type' => 'visit', 'check_in' => '2026-11-28', 'check_out' => '2026-11-30', 'guests' => null]);
        $this->assertSame([['key' => 'visit', 'label' => 'Visite', 'value' => '28 nov. 2026'], ['key' => 'second', 'label' => 'Seconde date', 'value' => '30 nov. 2026']], $visit->dateLines('fr'));
        // Le texte d'une visite : sa date, ou la seconde proposée (décision 2026-11-20-texte-demande-location).
        app()->setLocale('fr');
        $this->assertSame('Demande de visite « '.$this->offer->getTranslation('title', 'fr').' » le 28 nov. 2026 ou le 30 nov. 2026.', BookingText::summary($visit, 'requested'));
        $visit->update(['check_out' => null]);
        $this->assertSame([['key' => 'visit', 'label' => 'Visite', 'value' => '28 nov. 2026']], $visit->refresh()->dateLines('fr'));
    }

    public function test_a_visit_shows_its_charges_deposit_and_advance_when_set_and_the_total_of_the_months(): void
    {
        // Décisions 2026-11-20-charges-visite et 2026-11-20-caution-dans-le-total.
        $flat = Offer::factory()->for($this->owner, 'user')->create([
            'status' => 'published', 'price' => '250000', 'currency' => 'XAF',
            'details' => ['property_type' => 'apartment', 'rental_type' => 'long_term', 'bedrooms' => 2],
        ]);
        $visit = Booking::factory()->for($flat)->create(['type' => 'visit', 'check_in' => '2026-11-28', 'check_out' => null, 'guests' => null]);
        $lines = fn (): array => array_column((new BookingResource($visit->refresh()))->resolve(request())['lines'], 'value', 'label');
        app()->setLocale('fr');

        // Rien de renseigné : ni charges, ni caution, ni avance ; un total à 0 ne s'affiche pas
        // (décisions 2026-11-20-lignes-vides-masquees et 2026-11-20-total-positif).
        $this->assertSame([
            'Visite' => '28 nov. 2026',
            'Loyer mensuel' => Number::currency(250000, 'XAF', 'fr'),
        ], $lines());

        // Les mois dans le libellé, le loyer fois ces mois en valeur ; le total, leur somme, sans les
        // charges (décision 2026-11-20-caution-en-montant).
        $flat->update(['details' => [...$flat->details, 'charges' => 15000, 'deposit_months' => 2, 'advance_months' => 6]]);
        $this->assertSame([
            '2 mois de caution' => Number::currency(500000, 'XAF', 'fr'),
            '6 mois d’avance' => Number::currency(1500000, 'XAF', 'fr'),
            // Avec des charges : « Total (sans charges) » (décision 2026-11-20-total-sans-charges).
            'Total (sans charges)' => Number::currency(2000000, 'XAF', 'fr'),
        ], array_slice($lines(), -3));
        // Les charges, puis le loyer (décision 2026-11-20-loyer-apres-charges).
        // Avec des charges, « Loyer mensuel (sans charges) » (décision 2026-11-20-loyer-sans-charges-avec-charges).
        $this->assertSame(['Charges mensuelles', 'Loyer mensuel (sans charges)'], array_slice(array_keys($lines()), 1, 2));
    }

    public function test_the_lines_give_the_availability_date_before_the_arrival_when_set(): void
    {
        // Décision 2026-11-20-disponibilite-demande.
        app()->setLocale('fr');
        $flat = Offer::factory()->for($this->owner, 'user')->create([
            'status' => 'published', 'price' => '250000', 'currency' => 'XAF',
            'details' => ['property_type' => 'apartment', 'rental_type' => 'long_term', 'bedrooms' => 2, 'available_from' => '2026-12-01'],
        ]);
        $visit = Booking::factory()->for($flat)->create(['type' => 'visit', 'check_in' => '2026-11-28', 'check_out' => null, 'guests' => null]);
        $labels = fn (): array => array_column((new BookingResource($visit->refresh()))->resolve(request())['lines'], 'value', 'label');

        $this->assertSame(['Disponible dès le' => '1 déc. 2026', 'Visite' => '28 nov. 2026'], array_slice($labels(), 0, 2));

        // Sans date : pas de ligne.
        $flat->update(['details' => [...$flat->details, 'available_from' => null]]);
        $this->assertArrayNotHasKey('Disponible dès le', $labels());

        // Un séjour (meublé) : jamais, même avec une ancienne date (décision 2026-11-20-disponibilite-sans-meuble).
        $studio = Offer::factory()->for($this->owner, 'user')->create([
            'status' => 'published', 'price' => '85000', 'currency' => 'XAF',
            'details' => ['property_type' => 'studio', 'rental_type' => 'furnished', 'bedrooms' => 0, 'available_from' => '2026-12-01'],
        ]);
        $stay = Booking::factory()->for($studio)->create(['type' => 'stay', 'check_in' => '2026-12-01', 'check_out' => '2026-12-04', 'guests' => 2]);
        $this->assertNotContains('available_from', array_column((new BookingResource($stay))->resolve(request())['lines'], 'key'));
    }

    public function test_a_stay_shows_its_deposit_when_set_before_the_total_that_adds_it(): void
    {
        // Décision 2026-11-20-caution-dans-le-total.
        app()->setLocale('fr');
        $studio = Offer::factory()->for($this->owner, 'user')->create([
            'status' => 'published', 'price' => '85000', 'currency' => 'XAF',
            'details' => ['property_type' => 'studio', 'rental_type' => 'furnished', 'bedrooms' => 0],
        ]);
        $stay = Booking::factory()->for($studio)->create(['type' => 'stay', 'check_in' => '2026-12-01', 'check_out' => '2026-12-04', 'guests' => 2, 'total' => '255000', 'currency' => 'XAF']);
        $tail = fn (): array => array_slice(array_column((new BookingResource($stay->refresh()))->resolve(request())['lines'], 'value', 'label'), -2);

        // Sans caution : la ligne à 0 (décision 2026-11-20-caution-meuble-toujours).
        $this->assertSame(['Caution' => Number::currency(0, 'XAF', 'fr'), 'Total' => Number::currency(255000, 'XAF', 'fr')], $tail());

        $studio->update(['details' => [...$studio->details, 'deposit' => 100000]]);
        $this->assertSame(['Caution' => Number::currency(100000, 'XAF', 'fr'), 'Total' => Number::currency(355000, 'XAF', 'fr')], $tail());
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
