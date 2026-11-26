<?php

namespace Modules\Offer\Tests\Feature\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Modules\Auth\Models\User;
use Modules\Location\Models\Country;
use Modules\Offer\Database\Seeders\OfferDatabaseSeeder;
use Modules\Offer\Enums\BookingStatusEnum;
use Modules\Offer\Models\Booking;
use Modules\Offer\Models\Offer;
use Modules\Offer\Notifications\BookingRequestAccepted;
use Modules\Offer\Notifications\BookingRequestReceived;
use Modules\Offer\Notifications\OfferMessageReceived;
use Tests\TestCase;

/**
 * Filtrer ses notifications par logement et par statut du logement (décision
 * 2026-11-20-filtres-notifications).
 */
class NotificationFiltersTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['offer.domain' => 'rental']);
        Country::factory()->create(['code' => 'CM', 'currency' => 'XAF']);
        $this->seed(OfferDatabaseSeeder::class);
    }

    private function notify(User $user, string $title, ?Offer $offer, ?string $status = null): void
    {
        $user->notifications()->create([
            'id' => (string) Str::uuid(), 'type' => 'Test',
            'data' => ['title' => $title, 'body' => 'Texte', ...($offer === null ? [] : ['offer_id' => $offer->getKey()]), ...($status === null ? [] : ['booking_status' => $status])],
        ]);
    }

    /** @return list<string> */
    private function titles(User $user, string $query = ''): array
    {
        $this->app['auth']->forgetGuards();

        return array_column($this->withToken($user->createToken('iPhone')->plainTextToken)->withHeaders(['X-Country' => 'CM', 'Accept-Language' => 'fr'])
            ->getJson('/api/v1/notifications'.$query)->assertOk()->json('data.items'), 'title');
    }

    public function test_one_request_type_with_the_real_status_home_and_rental_type(): void
    {
        // Décisions 2026-11-20-filtres-notifications et 2026-11-20-statut-reel.
        $user = User::factory()->create(['locale' => 'fr']);
        $studio = Offer::factory()->create(['title' => ['fr' => 'Studio photos'], 'details' => ['rental_type' => 'furnished']]);
        $flat = Offer::factory()->create(['title' => ['fr' => 'Appartement Akwa'], 'details' => ['rental_type' => 'long_term']]);
        $pending = Booking::factory()->for($studio)->create();
        $accepted = Booking::factory()->for($flat)->status(BookingStatusEnum::ACCEPTED)->create();
        $create = fn (string $type, string $title, ?Offer $offer, ?Booking $booking) => $user->notifications()->create([
            'id' => (string) Str::uuid(), 'type' => $type,
            'data' => ['title' => $title, 'body' => 'Texte', ...($offer === null ? [] : ['offer_id' => $offer->getKey()]), ...($booking === null ? [] : ['booking_id' => $booking->getKey()])],
        ]);
        $create(BookingRequestReceived::class, 'Demande studio', $studio, $pending);
        // Reçue en attente, acceptée depuis : son statut réel est « Acceptée ».
        $create(BookingRequestReceived::class, 'Demande appartement', $flat, $accepted);
        $create(BookingRequestAccepted::class, 'Acceptée appartement', $flat, $accepted);
        $create('Autre', 'Sans logement', null, null);
        // Un message à l'éditeur : le type « Message » (décision 2026-11-26-filtre-type-en-puces).
        $create(OfferMessageReceived::class, 'Un message', null, null);

        $this->app['auth']->forgetGuards();
        $filters = collect($this->withToken($user->createToken('iPhone')->plainTextToken)->withHeaders(['X-Country' => 'CM', 'Accept-Language' => 'fr'])
            ->getJson('/api/v1/notifications')->json('data.filters'))->keyBy('name');
        // « Statut » : commun, juste après « Type », ses options selon le groupe du type (décision
        // 2026-11-24-notifications-types-groupes-statut).
        $this->assertSame(['user', 'type', 'status', 'offer', 'rental_type', 'unread'], $filters->keys()->all());
        $this->assertSame('type', $filters['status']['depends_on']);
        $this->assertSame('booking', collect($filters['status']['options'])->firstWhere('value', 'accepted')['parent']);
        $this->assertSame('partner', collect($filters['status']['options'])->firstWhere('value', 'partner_published')['parent']);
        // « Réservation » seulement, toutes les actions (décision 2026-11-24-notifications-types-reservation-partenaire) ;
        // un type inconnu des modules n'est pas proposé.
        $this->assertSame(['booking' => 'Réservation', 'offer_message' => 'Message'], array_column($filters['type']['options'], 'label', 'value'));
        // En puces : un ou plusieurs types (décision 2026-11-26-filtre-type-en-puces).
        $this->assertSame('chips', $filters['type']['type']);
        // Une seule option ne verrouille pas un filtre ni n'en fait la valeur par défaut : l'app ne la prend pas pour « Tous »
        // (décision 2026-11-20-filtre-option-unique).
        $this->assertFalse($filters['type']['locked']);
        $this->assertNull($filters['type']['default']);
        $this->assertArrayNotHasKey('selected', $filters['type']);
        // Logement et type de location : pour « Réservation » seulement ; « Statut » : dès qu'un type est choisi, ses
        // options selon le type.
        foreach (['offer', 'rental_type'] as $name) {
            $this->assertSame(['booking'], $filters[$name]['visible_when']['values']);
        }
        $this->assertSame(['booking', 'partner'], $filters['status']['visible_when']['values']);
        $this->assertSame(['pending', 'accepted', 'refused', 'cancelled', 'completed', 'expired'], array_column(array_filter($filters['status']['options'], fn (array $option): bool => $option['parent'] === 'booking'), 'value'));
        $this->assertSame(['long_term' => 'Location', 'furnished' => 'Location meublée', 'shared' => 'Colocation'], array_column($filters['rental_type']['options'], 'label', 'value'));
        $this->assertTrue($filters['offer']['searchable']);
        $this->assertSame(['Appartement Akwa', 'Studio photos'], array_column($filters['offer']['options'], 'label'));

        $this->assertCount(3, $this->titles($user, '?filter[type]=booking'));
        $this->assertCount(3, $this->titles($user, '?filter[type][]=booking'));
        $this->assertSame(['Un message'], $this->titles($user, '?filter[type][]=offer_message'));
        $this->assertCount(4, $this->titles($user, '?filter[type][]=booking&filter[type][]=offer_message'));
        // Le statut réel de la demande, pas l'étape notifiée.
        $this->assertSame(['Demande studio'], $this->titles($user, '?filter[type]=booking&filter[status]=pending'));
        $this->assertEqualsCanonicalizing(['Demande appartement', 'Acceptée appartement'], $this->titles($user, '?filter[type]=booking&filter[status]=accepted'));
        $this->assertSame(['Demande studio'], $this->titles($user, "?filter[offer]={$studio->getKey()}"));
        $this->assertSame(['Demande studio'], $this->titles($user, '?filter[rental_type]=furnished'));
        // Un type inconnu ne filtre rien.
        $this->assertCount(5, $this->titles($user, '?filter[type]=Inconnu'));
        $this->assertCount(5, $this->titles($user, '?filter[type][]=Inconnu'));
    }

    public function test_the_search_finds_who_acted_and_the_home_title(): void
    {
        $user = User::factory()->create(['locale' => 'fr']);
        $paul = User::factory()->create(['first_name' => 'Paul', 'last_name' => 'Etoa']);
        $studio = Offer::factory()->create(['title' => ['fr' => 'Studio photos', 'en' => 'Photo studio']]);
        $flat = Offer::factory()->create(['title' => ['fr' => 'Appartement Akwa']]);
        $user->notifications()->create(['id' => (string) Str::uuid(), 'type' => 'Test', 'user_id' => $paul->getKey(), 'data' => ['title' => 'De Paul', 'body' => 'Texte', 'offer_id' => $flat->getKey()]]);
        $this->notify($user, 'Pour le studio', $studio);
        $this->notify($user, 'Sans rien', null);

        $this->assertSame(['De Paul'], $this->titles($user, '?search=paul%20etoa'));
        $this->assertSame(['De Paul'], $this->titles($user, '?search=etoa'));
        $this->assertSame(['Pour le studio'], $this->titles($user, '?search=photo'));
        $this->assertSame(['De Paul'], $this->titles($user, '?search=akwa'));
        $this->assertSame([], $this->titles($user, '?search=inconnu'));
        // Recherche et filtre ensemble.
        $this->assertSame([], $this->titles($user, "?search=paul&filter[offer]={$studio->getKey()}"));
    }

    public function test_notifications_are_filtered_by_who_acted(): void
    {
        $user = User::factory()->create(['locale' => 'fr']);
        $paul = User::factory()->create(['first_name' => 'Paul', 'last_name' => 'Etoa']);
        $awa = User::factory()->create(['first_name' => 'Awa', 'last_name' => 'Ngono']);
        User::factory()->create(['first_name' => 'Hors', 'last_name' => 'Liste']);
        foreach ([[$paul, 'De Paul'], [$awa, 'D’Awa']] as [$actor, $title]) {
            $user->notifications()->create(['id' => (string) Str::uuid(), 'type' => 'Test', 'user_id' => $actor->getKey(), 'data' => ['title' => $title, 'body' => 'Texte']]);
        }
        $this->notify($user, 'Sans membre', null);

        $this->app['auth']->forgetGuards();
        $filters = $this->withToken($user->createToken('iPhone')->plainTextToken)->withHeaders(['X-Country' => 'CM', 'Accept-Language' => 'fr'])
            ->getJson('/api/v1/notifications')->json('data.filters');
        $this->assertSame(['Awa Ngono', 'Paul Etoa'], array_column(collect($filters)->firstWhere('name', 'user')['options'], 'label'));
        // « Expéditeur » : qui a envoyé la notification (décision 2026-11-26-filtre-expediteur).
        $this->assertSame('Expéditeur', collect($filters)->firstWhere('name', 'user')['label']);

        $this->assertSame(['De Paul'], $this->titles($user, "?filter[user]={$paul->getKey()}"));
    }

    public function test_notifications_are_filtered_by_unread_a_thread_counting_as_one_card(): void
    {
        // Décision 2026-11-21-filtre-non-lues.
        $user = User::factory()->create(['locale' => 'fr']);
        $make = fn (string $title, ?string $thread, bool $read, int $minutes) => $user->notifications()->create([
            'id' => (string) Str::uuid(), 'type' => 'Test', 'thread' => $thread, 'read_at' => $read ? now() : null,
            'created_at' => now()->subMinutes($minutes), 'data' => ['title' => $title, 'body' => 'Texte'],
        ]);
        $make('Seule lue', null, true, 50);
        $make('Seule non lue', null, false, 40);
        // Un fil dont la dernière étape est lue mais une plus ancienne ne l'est pas : la carte est non lue.
        $make('Fil ancienne', 'booking:1', false, 30);
        $make('Fil dernière', 'booking:1', true, 20);
        // Un fil tout lu.
        $make('Fil lu', 'booking:2', true, 10);

        $this->app['auth']->forgetGuards();
        $filter = collect($this->withToken($user->createToken('iPhone')->plainTextToken)->withHeaders(['X-Country' => 'CM', 'Accept-Language' => 'fr'])
            ->getJson('/api/v1/notifications')->json('data.filters'))->firstWhere('name', 'unread');
        $this->assertSame('segmented', $filter['type']);
        $this->assertSame('Non lues', $filter['label']);
        $this->assertSame('any', $filter['default']);
        $this->assertSame(['any' => 'Tous', 'yes' => 'Oui', 'no' => 'Non'], array_column($filter['options'], 'label', 'value'));

        $this->assertEqualsCanonicalizing(['Fil dernière', 'Seule non lue'], $this->titles($user, '?filter[unread]=yes'));
        $this->assertEqualsCanonicalizing(['Fil lu', 'Seule lue'], $this->titles($user, '?filter[unread]=no'));
        $this->assertCount(4, $this->titles($user, '?filter[unread]=any'));
    }
}
