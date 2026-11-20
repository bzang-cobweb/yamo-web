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
use Modules\Offer\Notifications\BookingRequested;
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
        $create(BookingRequested::class, 'Demande studio', $studio, $pending);
        // Reçue en attente, acceptée depuis : son statut réel est « Acceptée ».
        $create(BookingRequested::class, 'Demande appartement', $flat, $accepted);
        $create(BookingRequested::class, 'Acceptée appartement', $flat, $accepted);
        $create('Autre', 'Sans logement', null, null);

        $this->app['auth']->forgetGuards();
        $filters = collect($this->withToken($user->createToken('iPhone')->plainTextToken)->withHeaders(['X-Country' => 'CM', 'Accept-Language' => 'fr'])
            ->getJson('/api/v1/notifications')->json('data.filters'))->keyBy('name');
        $this->assertSame(['user', 'type', 'status', 'offer', 'rental_type'], $filters->keys()->all());
        // Un seul type pour les quatre étapes d'une demande ; un type inconnu des modules n'est pas proposé.
        $this->assertSame(['booking' => 'Demande de logement'], array_column($filters['type']['options'], 'label', 'value'));
        // Une seule option ne verrouille pas un filtre ni n'en fait la valeur par défaut : l'app ne la prend pas pour « Tous »
        // (décision 2026-11-20-filtre-option-unique).
        $this->assertFalse($filters['type']['locked']);
        $this->assertNull($filters['type']['default']);
        // Statut, logement et type de location : pour ce type seulement.
        foreach (['status', 'offer', 'rental_type'] as $name) {
            $this->assertSame(['booking'], $filters[$name]['visible_when']['values']);
        }
        $this->assertSame(['pending', 'accepted', 'refused', 'cancelled'], array_column($filters['status']['options'], 'value'));
        $this->assertSame(['long_term' => 'Location longue durée', 'furnished' => 'Location meublée', 'shared' => 'Colocation'], array_column($filters['rental_type']['options'], 'label', 'value'));
        $this->assertTrue($filters['offer']['searchable']);
        $this->assertSame(['Appartement Akwa', 'Studio photos'], array_column($filters['offer']['options'], 'label'));

        $this->assertCount(3, $this->titles($user, '?filter[type]=booking'));
        // Le statut réel de la demande, pas l'étape notifiée.
        $this->assertSame(['Demande studio'], $this->titles($user, '?filter[type]=booking&filter[status]=pending'));
        $this->assertEqualsCanonicalizing(['Demande appartement', 'Acceptée appartement'], $this->titles($user, '?filter[type]=booking&filter[status]=accepted'));
        $this->assertSame(['Demande studio'], $this->titles($user, "?filter[offer]={$studio->getKey()}"));
        $this->assertSame(['Demande studio'], $this->titles($user, '?filter[rental_type]=furnished'));
        // Un type inconnu ne filtre rien.
        $this->assertCount(4, $this->titles($user, '?filter[type]=Inconnu'));
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

        $this->assertSame(['De Paul'], $this->titles($user, "?filter[user]={$paul->getKey()}"));
    }
}
