<?php

namespace Modules\Offer\Tests\Feature\Api;

use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Number;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use Modules\Auth\Models\User;
use Modules\Auth\Sms\SmsSender;
use Modules\Auth\Support\PhoneNumbers;
use Modules\Auth\Tests\Concerns\FakeSmsSender;
use Modules\Location\Models\Address;
use Modules\Location\Models\Country;
use Modules\Offer\Database\Seeders\OfferDatabaseSeeder;
use Modules\Offer\Http\Resources\BookingResource;
use Modules\Offer\Models\Booking;
use Modules\Offer\Models\Offer;
use Modules\Offer\Notifications\BookingReviewInvitation;
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
        // Le titre de la carte : le type de l'action, sa dernière étape (décision 2026-11-22-notification-par-action).
        $this->assertSame('Demande annulée', $owner[0]['card_title']);
        // Le membre : une carte, l'acceptation.
        $this->assertCount(1, $this->cards($this->guest));

        $expected = ['Demande envoyée', 'Demande acceptée', 'Demande annulée'];
        foreach ([[$this->owner, $owner[0]['id']], [$this->guest, $this->cards($this->guest)[0]['id']]] as [$reader, $id]) {
            $history = $this->api($reader, 'GET', '/'.$id)->assertOk()->json('data.history');
            // Les deux côtés, de la plus ancienne étape à la plus récente.
            $this->assertSame($expected, array_column($history, 'label'));
            $this->assertSame([(string) $this->guest->getKey(), (string) $this->owner->getKey(), (string) $this->guest->getKey()], array_column(array_column($history, 'actor'), 'id'));
            $this->assertSame(['Bonjour !', null, null], array_column($history, 'message'));
            $this->assertSame([false, false, false], array_column($history, 'edited'));
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
        $this->assertStringStartsWith('Vous avez accepté la demande de séjour de '.$this->guest->full_name, $cards[0]['body']);
        $this->assertNull($cards[0]['actor']);
        $this->assertNull($cards[0]['summary']);
        $this->assertSame(['label' => 'Acceptée', 'tone' => 'success'], $cards[0]['tag']);
        $this->assertSame('/offers/'.$this->offer->getKey().'/calendar?month='.$first->check_in->format('Y-m'), $cards[0]['link']);
        // Une seule notification par action, au membre (décision 2026-11-24-notifications-fil-de-groupe) : le
        // propriétaire n'en reçoit pas pour sa réponse, qui ne compte pas comme non lue ; restent les deux demandes reçues.
        $this->assertSame(0, $this->owner->notifications()->where('user_id', $this->owner->getKey())->count());
        $this->assertSame(2, $this->api($this->owner, 'GET', '/unread-count')->json('data.unread'));
        // Le suivi : la demande reçue, puis sa réponse, de lui.
        $history = $this->api($this->owner, 'GET', '/'.$cards[0]['id'])->assertOk()->json('data.history');
        $this->assertSame(['Demande envoyée', 'Demande acceptée'], array_column($history, 'label'));
        $this->assertSame((string) $this->owner->getKey(), $history[1]['actor']['id']);
    }

    public function test_the_member_sees_the_offer_contact_or_else_the_owner_one(): void
    {
        // Décision 2026-11-21-contact-offre.
        $this->owner->forceFill(['email' => 'hote@example.test', 'phone' => '+237670000001'])->save();
        $this->request(3);
        $contact = fn (): array => array_column(array_filter(
            $this->api($this->guest, 'GET', '/'.$this->cards($this->guest)[0]['id'])->json('data.booking.lines'),
            fn (array $line): bool => in_array($line['key'], ['phone', 'email'], true),
        ), 'value', 'key');

        $this->assertSame(['phone' => PhoneNumbers::international('+237670000001'), 'email' => 'hote@example.test'], $contact());
        $this->offer->update(['phone' => '+237690000002', 'email' => 'logement@example.test']);
        $this->assertSame(['phone' => PhoneNumbers::international('+237690000002'), 'email' => 'logement@example.test'], $contact());
    }

    public function test_the_owner_shows_or_hides_the_address_to_the_requester(): void
    {
        // Décision 2026-11-21-afficher-adresse : la demande part du choix de l'offre ; le propriétaire le change.
        $this->offer->offerAddress()->create(['address_id' => Address::factory()->create(['street' => 'Rue Joss', 'zip' => '', 'city' => 'Douala'])->getKey()]);
        $this->offer->update(['show_address' => false]);
        $booking = $this->request(3);
        $this->assertFalse($booking->show_address);
        $keys = fn (User $reader): array => array_column($this->api($reader, 'GET', '/'.$this->cards($reader)[0]['id'])->json('data.booking.lines'), 'key');

        // Masquée : la ville seule pour le demandeur (décision 2026-11-21-adresse-masquee-ville) ; le
        // propriétaire voit l'adresse entière, après le titre composé et les dates (décision 2026-11-22-detail-lignes-titre-dates-adresse).
        $lines = collect($this->api($this->guest, 'GET', '/'.$this->cards($this->guest)[0]['id'])->json('data.booking.lines'))->keyBy('key');
        $this->assertSame('Douala', $lines['address']['value']);
        $this->assertSame(['arrival', 'departure', 'address'], array_slice($keys($this->owner), 0, 3));

        $this->app['auth']->forgetGuards();
        $this->withToken($this->guest->createToken('iPhone')->plainTextToken)->postJson("/api/v1/bookings/{$booking->getKey()}/address/show")->assertNotFound();
        $this->app['auth']->forgetGuards();
        $this->withToken($this->owner->createToken('iPhone')->plainTextToken)->postJson("/api/v1/bookings/{$booking->getKey()}/address/show")->assertNoContent();
        $this->assertTrue($booking->fresh()->show_address);
        $this->assertSame(['arrival', 'departure', 'address'], array_slice($keys($this->guest), 0, 3));
        $this->assertNotContains('host', $keys($this->guest));

        $this->app['auth']->forgetGuards();
        $this->withToken($this->owner->createToken('iPhone')->plainTextToken)->postJson("/api/v1/bookings/{$booking->getKey()}/address/hide")->assertNoContent();
        $this->assertFalse($booking->fresh()->show_address);
    }

    public function test_each_side_always_sees_the_other_ones_name_phone_and_email(): void
    {
        // Décision 2026-11-21-detail-reservation-contact.
        $this->owner->forceFill(['first_name' => 'Paul', 'last_name' => 'Hôte', 'email' => 'hote@example.test', 'phone' => '+237690000001'])->save();
        $this->guest->forceFill(['first_name' => 'Léa', 'last_name' => 'Membre', 'email' => 'membre@example.test', 'phone' => null])->save();
        $this->request(3);
        $contact = fn (User $reader): array => array_values(array_filter(
            $this->api($reader, 'GET', '/'.$this->cards($reader)[0]['id'])->assertOk()->json('data.booking.lines'),
            fn (array $line): bool => in_array($line['key'], ['host', 'member', 'phone', 'email'], true),
        ));

        // Toujours, même en attente (décision 2026-11-21-contact-toujours) ; pour le membre, le téléphone et l'e-mail de
        // l'offre, sans le nom de l'hôte (décision 2026-11-21-detail-reservation-contact-ordre).
        $host = $contact($this->guest);
        $this->assertSame([
            ['key' => 'phone', 'label' => 'Téléphone', 'value' => PhoneNumbers::international('+237690000001')],
            ['key' => 'email', 'label' => 'E-mail', 'value' => 'hote@example.test'],
        ], $host);
        // Sans téléphone : pas de ligne.
        $this->assertSame([
            ['key' => 'member', 'label' => 'Membre', 'value' => 'Léa Membre', 'link' => '/members/'.$this->guest->getKey()],
            ['key' => 'email', 'label' => 'E-mail', 'value' => 'membre@example.test'],
        ], $contact($this->owner));
        // Après l'adresse ; sans adresse, après le titre composé et les dates (décision 2026-11-22-detail-lignes-titre-dates-adresse).
        $lines = $this->api($this->guest, 'GET', '/'.$this->cards($this->guest)[0]['id'])->json('data.booking.lines');
        $this->assertSame(['arrival', 'departure', 'phone', 'email', 'guests'], array_slice(array_column($lines, 'key'), 0, 5));
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

            // La barre du haut : « Ma réservation » pour le membre, « Demande reçue » pour le propriétaire (décision
            // 2026-11-22-detail-titre-compose-et-dates) ; le titre composé sans ville, la première ligne (décision 2026-11-22-detail-lignes-titre-dates-adresse).
            $this->assertSame($reader->is($this->guest) ? 'Ma réservation' : 'Demande reçue', $page['title']);
            // Le titre composé, sous le nom du logement (décision 2026-11-22-detail-titre-compose-sous-titre) : sans adresse, pas de ville.
            $this->assertSame('Location meublée · Studio', $page['booking']['composed_title']);
            $this->assertSame(['label' => 'Refusée', 'tone' => 'neutral'], $page['tag']);
            // L'en-tête, l'étape de la carte ouverte, le refus (décision 2026-11-24-entete-etape-ouverte) : chez le membre,
            // reçu du propriétaire ; chez le propriétaire, sa propre réponse, sans qui agit ni résumé.
            if ($reader->is($this->guest)) {
                $this->assertSame('Votre demande de séjour pour « '.$this->offer->getTranslation('title', 'fr').' » ('.$booking->period('fr').', 1 personne) a été refusée.', $page['summary']);
                $this->assertSame((string) $this->owner->getKey(), $page['actor']['id']);
            } else {
                $this->assertNull($page['summary']);
                $this->assertNull($page['actor']);
            }
            // La demande, ajoutée par le module Offer (décision 2026-11-20-notification-generique).
            $this->assertArrayNotHasKey('total', $page);
            $this->assertSame([
                'id' => (string) $booking->getKey(), 'offer_id' => (string) $this->offer->getKey(), 'type' => 'stay', 'status' => 'refused',
                'check_in' => $booking->check_in->toDateString(), 'check_out' => $booking->check_out?->toDateString(), 'guests' => 1,
                'offer' => ['title' => $this->offer->getTranslation('title', 'fr'), 'rental_type' => 'Location meublée', 'property_type' => 'Studio'],
            ], array_diff_key($page['booking'], ['lines' => true, 'composed_title' => true]));
            // Les lignes, libellé et valeur, dans l'ordre ; sans chambres renseignées, pas de ligne (décision 2026-11-20-lignes-demande).
            $this->assertSame([
                ['key' => 'arrival', 'label' => 'Arrivée', 'value' => $booking->check_in->locale('fr')->isoFormat('ll')],
                ['key' => 'departure', 'label' => 'Départ', 'value' => (string) $booking->check_out?->locale('fr')->isoFormat('ll')],
                ['key' => 'guests', 'label' => 'Personnes', 'value' => '1'],
                ['key' => 'days', 'label' => 'Durée', 'value' => '2 jours'],
                ['key' => 'price', 'label' => 'Prix par jour', 'value' => Number::currency(45000, 'XAF', 'fr')],
                // La caution du meublé, toujours (décision 2026-11-20-caution-meuble-toujours).
                ['key' => 'deposit', 'label' => 'Caution', 'value' => Number::currency(0, 'XAF', 'fr')],
                ['key' => 'total', 'label' => 'Total', 'value' => (string) $booking->formattedTotal('fr')],
            ], array_values(array_filter($page['booking']['lines'], fn (array $line): bool => ! in_array($line['key'], ['host', 'member', 'phone', 'email'], true))));
        }

        // L'adresse du logement, avant les dates (décision 2026-11-21-detail-reservation-adresse).
        $this->offer->offerAddress()->create(['address_id' => Address::factory()->create(['street' => 'Rue Joss', 'zip' => '', 'city' => 'Douala'])->getKey()]);
        $resource = (new BookingResource($booking->refresh()))->resolve(request());
        // La ville au bout du titre composé (décision 2026-11-23-detail-titre-compose-avec-ville).
        $this->assertSame('Location meublée · Studio · Douala', $resource['composed_title']);
        $lines = $resource['lines'];
        // Après le titre composé et les dates (décision 2026-11-22-detail-lignes-titre-dates-adresse).
        $this->assertSame(['arrival', 'departure', 'address', 'guests'], array_slice(array_column($lines, 'key'), 0, 4));
        $this->assertSame('Adresse', $lines[2]['label']);
        $this->assertStringStartsWith('Rue Joss, Douala', $lines[2]['value']);
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

    public function test_the_reader_is_not_among_the_members_of_their_own_actions(): void
    {
        // Décision 2026-11-24-notifications-fil-de-groupe : sa réponse, reçue par le membre, porte son nom (`user_id`) ;
        // le filtre « Membre » ne le propose pas ; son fil montre ce qu'il a reçu et ce qu'il a fait.
        $booking = $this->request(3);
        $this->travel(5)->minutes();
        app(BookingService::class)->answer($booking, true);

        $members = collect($this->api($this->owner, 'GET', '')->json('data.filters'))->keyBy('name')['user']['options'];
        $this->assertSame([(string) $this->guest->getKey()], array_column($members, 'value'));
        $this->assertSame(1, $this->owner->notifications()->where('thread', $booking->thread())->count());
        $history = $this->api($this->owner, 'GET', '/'.$this->cards($this->owner)[0]['id'])->json('data.history');
        $this->assertCount(2, $history);
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

        // Une visite : sa seule date, même avec une ancienne seconde date (décision 2026-11-22-visite-une-seule-date).
        $visit = Booking::factory()->for($this->offer)->create(['type' => 'visit', 'check_in' => '2026-11-28', 'check_out' => '2026-11-30', 'guests' => null]);
        $this->assertSame([['key' => 'visit', 'label' => 'Visite', 'value' => '28 nov. 2026']], $visit->dateLines('fr'));
        app()->setLocale('fr');
        $this->assertSame('Une demande de visite pour « '.$this->offer->getTranslation('title', 'fr').' » (le 28 nov. 2026) a été enregistrée.', BookingText::summary($visit, 'requested'));
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

    public function test_the_lines_give_the_availability_date_after_the_dates_when_set(): void
    {
        // Décision 2026-11-20-disponibilite-demande.
        app()->setLocale('fr');
        $flat = Offer::factory()->for($this->owner, 'user')->create([
            'status' => 'published', 'price' => '250000', 'currency' => 'XAF',
            'details' => ['property_type' => 'apartment', 'rental_type' => 'long_term', 'bedrooms' => 2, 'available_from' => '2026-12-01'],
        ]);
        $visit = Booking::factory()->for($flat)->create(['type' => 'visit', 'check_in' => '2026-11-28', 'check_out' => null, 'guests' => null]);
        $labels = fn (): array => array_column((new BookingResource($visit->refresh()))->resolve(request())['lines'], 'value', 'label');

        // Après la date de visite, avant l'adresse (décision 2026-11-22-detail-disponible-avant-adresse).
        $this->assertSame(['Visite' => '28 nov. 2026', 'Disponible dès le' => '1 déc. 2026'], array_slice($labels(), 0, 2));
        $flat->offerAddress()->create(['address_id' => Address::factory()->create(['street' => 'Rue Joss', 'zip' => '', 'city' => 'Douala'])->getKey()]);
        $this->assertSame(['Visite', 'Disponible dès le', 'Adresse'], array_slice(array_keys($labels()), 0, 3));

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
        $this->assertFalse($cards['Demande annulée']['read']);

        $this->api($this->owner, 'POST', '/'.$cards['Demande annulée']['id'].'/read')->assertNoContent();

        $this->assertSame(1, $this->api($this->owner, 'GET', '/unread-count')->json('data.unread'));
        $this->assertTrue(collect($this->cards($this->owner))->keyBy('title')['Demande annulée']['read']);
    }

    public function test_opening_a_request_from_a_read_step_still_marks_the_whole_thread_read(): void
    {
        // Décision 2026-11-22-detail-marque-tout-le-fil.
        $booking = $this->request(3);
        $this->travel(5)->minutes();
        app(BookingService::class)->answer($booking, true);
        // Sa propre demande, reçue par le propriétaire (décision 2026-11-24-notifications-fil-de-groupe).
        $own = DatabaseNotification::query()->where('thread', $booking->thread())->where('user_id', $this->guest->getKey())->sole();
        $this->assertSame(1, $this->guest->unreadNotifications()->where('thread', $booking->thread())->count());

        // Sa propre demande ouverte : la page dit non lue, puisqu'une étape du fil qu'il a reçue l'est encore.
        $this->assertFalse($this->api($this->guest, 'GET', '/'.$own->getKey())->assertOk()->json('data.read'));
        // L'app la marque lue : tout le fil l'est.
        $this->api($this->guest, 'POST', '/'.$own->getKey().'/read')->assertNoContent();
        $this->assertSame(0, $this->guest->unreadNotifications()->where('thread', $booking->thread())->count());
        $this->assertTrue($this->api($this->guest, 'GET', '/'.$own->getKey())->json('data.read'));
    }

    public function test_the_requester_button_edits_the_request_while_it_is_pending(): void
    {
        $booking = $this->request(3);
        $page = fn (User $reader): array => $this->api($reader, 'GET', '/'.$this->cards($reader)[0]['id'])->assertOk()->json('data');

        // Le membre, demande en attente : « Modifier » ouvre le formulaire prérempli.
        $this->assertSame('/profile/offer.booking.edit?record='.$booking->getKey(), $page($this->guest)['link']);
        $this->assertSame('Modifier', $page($this->guest)['link_label']);
        // Le propriétaire garde « Traiter la demande ».
        $this->assertSame('Traiter la demande', $page($this->owner)['link_label']);

        // « Annuler », second bouton, ouvre le formulaire d'annulation (décision 2026-11-21-annuler-depuis-le-detail).
        $this->assertSame(['/profile/offer.booking.cancel?record='.$booking->getKey(), 'Annuler'], [$page($this->guest)['secondary_link'], $page($this->guest)['secondary_link_label']]);
        $this->assertArrayNotHasKey('secondary_link', $page($this->owner));

        // Une fois répondue : « Mes réservations » revient.
        app(BookingService::class)->answer($booking, true);
        $this->assertNull($page($this->owner)['link']);
        $this->assertSame('/profile/lists/offer.bookings', $page($this->guest)['link']);
        $this->assertSame('Mes réservations', $page($this->guest)['link_label']);
        // Acceptée, pas encore commencée : toujours annulable.
        $this->assertSame('Annuler', $page($this->guest)['secondary_link_label']);

        // Terminée : « Laisser un avis », tant qu'il n'en a pas donné (décision 2026-11-22-avis-apres-demande-terminee).
        $this->travel(10)->days();
        $this->assertSame(['/offers/'.$booking->offer_id.'/review', 'Laisser un avis'], [$page($this->guest)['link'], $page($this->guest)['link_label']]);
        // Le propriétaire, à la place de « Traiter la demande » : « Inviter à donner un avis » ouvre son formulaire, qui
        // envoie l'invitation (décision 2026-11-22-inviter-avis-depuis-la-demande).
        $this->assertSame(['/profile/offer.booking.invite-review?record='.$booking->getKey(), 'Inviter à donner un avis'], [$page($this->owner)['link'], $page($this->owner)['link_label']]);
        $call = function (User $user, string $method, string $uri): TestResponse {
            $this->app['auth']->forgetGuards();

            return $this->withToken($user->createToken('iPhone')->plainTextToken)->withHeaders(['X-Country' => 'CM', 'Accept-Language' => 'fr'])->json($method, '/api/v1'.$uri);
        };
        $form = $call($this->owner, 'GET', '/forms/offer.booking.invite-review?record='.$booking->getKey())->assertOk()->json('data');
        $this->assertSame(['Inviter à donner un avis', [], 'POST', "/v1/bookings/{$booking->getKey()}/invite-review"], [$form['title'], $form['fields'], $form['submit']['method'], $form['submit']['endpoint']]);
        $call($this->guest, 'POST', "/bookings/{$booking->getKey()}/invite-review")->assertNotFound();
        $call($this->owner, 'POST', "/bookings/{$booking->getKey()}/invite-review")->assertNoContent();
        $this->assertNotNull($booking->refresh()->review_invited_at);
        $invitation = DatabaseNotification::query()->where('notifiable_id', $this->guest->getKey())->where('type', BookingReviewInvitation::class)->sole();
        $this->assertSame('Votre avis', $invitation->data['title']);
        // Une carte à elle, en tête du membre : retirée pour relire la page de la demande.
        $invitation->delete();
        // Avec un avis du membre : plus de bouton, ni d'invitation possible.
        $booking->offer->reviews()->create(['user_id' => $this->guest->getKey(), 'rating' => 5, 'comment' => 'Parfait.']);
        $this->assertNull($page($this->owner)['link']);
        $call($this->owner, 'POST', "/bookings/{$booking->getKey()}/invite-review")->assertForbidden();
        $this->assertSame('/profile/lists/offer.bookings', $page($this->guest)['link']);
        $this->travelBack();

        // Annulée avec un message : plus de bouton, le message dans le suivi du propriétaire.
        $this->travel(5)->minutes();
        app(BookingService::class)->cancel($booking->refresh(), 'Empêchement.');
        $this->assertArrayNotHasKey('secondary_link', $page($this->guest));
        $this->assertSame('Empêchement.', collect($page($this->owner)['history'])->last()['message']);
    }

    public function test_an_edited_request_replaces_its_step_by_a_new_unread_one_for_the_owner(): void
    {
        $this->travelTo(now()->setTime(9, 0));
        $booking = $this->request(3, 'Premier message');
        $this->api($this->owner, 'POST', '/'.$this->cards($this->owner)[0]['id'].'/read')->assertNoContent();
        $this->travel(5)->minutes();

        $opened = $this->cards($this->guest)[0]['id'];

        app(BookingService::class)->update($booking, [
            'check_in' => today()->addDays(5)->toDateString(), 'check_out' => today()->addDays(9)->toDateString(), 'guests' => 2, 'message' => 'Message modifié',
        ]);

        // Le propriétaire : une notification « Demande modifiée », non lue, à la place de la première.
        $this->assertSame(1, $this->owner->notifications()->count());
        $card = $this->cards($this->owner)[0];
        $this->assertFalse($card['read']);
        $this->assertStringContainsString('modifiée', (string) $card['summary']);
        $this->assertStringContainsString(', 2 personnes)', (string) $card['summary']);
        $history = $this->api($this->owner, 'GET', '/'.$card['id'])->json('data.history');
        $this->assertSame(['Demande modifiée'], array_column($history, 'label'));
        // « Modifié » près de sa date (décision 2026-11-21-etape-modifiee-date).
        $this->assertSame([true], array_column($history, 'edited'));
        $this->assertSame(['Message modifié'], array_column($history, 'message'));
        // Le membre : aucune notification reçue ; sa demande, réécrite sur place chez le propriétaire, garde son
        // identifiant (la page qu'il a ouverte reste valable), rédigée pour lui, lue (décision
        // 2026-11-24-notifications-fil-de-groupe).
        $this->assertSame(0, $this->guest->notifications()->count());
        $mine = $this->cards($this->guest)[0];
        $this->assertSame($opened, $mine['id']);
        $this->assertStringContainsString('Vous avez modifié votre demande', $mine['body']);
        $this->assertTrue($mine['read']);
        $this->assertSame(['Message modifié'], array_column($this->api($this->guest, 'GET', '/'.$mine['id'])->json('data.history'), 'message'));
    }

    public function test_the_in_app_step_is_written_before_the_api_answers_and_the_other_channels_are_queued(): void
    {
        $booking = $this->request(3);
        // Une vraie file, sans travailleur : ce qui y part n'est pas traité.
        config(['queue.default' => 'database']);

        app(BookingService::class)->update($booking, [
            'check_in' => today()->addDays(5)->toDateString(), 'check_out' => today()->addDays(9)->toDateString(), 'guests' => 1, 'message' => 'Message modifié',
        ]);

        // Sans travailleur de file : la nouvelle étape est déjà dans le fil du propriétaire.
        $this->assertSame('updated', $this->owner->notifications()->sole()->data['step']);
        // Les autres canaux du propriétaire (e-mail) attendent en file.
        $this->assertGreaterThan(0, DB::table('jobs')->count());
    }

    public function test_an_edited_request_is_marked_edited_only_when_its_message_changes(): void
    {
        $booking = $this->request(3, 'Bonjour !');
        $history = fn (): array => $this->api($this->owner, 'GET', '/'.$this->cards($this->owner)[0]['id'])->json('data.history');
        $edit = fn (string $message, int $from) => app(BookingService::class)->update($booking->refresh(), [
            'check_in' => today()->addDays($from)->toDateString(), 'check_out' => today()->addDays($from + 2)->toDateString(), 'guests' => 1, 'message' => $message,
        ]);

        // Dates changées, même message : pas « Modifié ».
        $edit('Bonjour !', 5);
        $this->assertSame([false], array_column($history(), 'edited'));
        // Message changé : « Modifié ».
        $edit('Bonsoir !', 5);
        $this->assertSame([true], array_column($history(), 'edited'));
        // Déjà modifiée, elle le reste.
        $edit('Bonsoir !', 7);
        $this->assertSame([true], array_column($history(), 'edited'));
        $this->assertTrue($this->owner->notifications()->sole()->data['edited']);
    }

    public function test_a_stranger_gets_no_follow_up(): void
    {
        $this->request(3);
        $id = $this->cards($this->owner)[0]['id'];

        $this->api(User::factory()->create(), 'GET', '/'.$id)->assertNotFound();
    }
}
