<?php

namespace Modules\Offer\Tests\Feature\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Modules\Auth\Models\User;
use Modules\Location\Models\Country;
use Modules\Offer\Database\Seeders\OfferDatabaseSeeder;
use Modules\Offer\Models\Offer;
use Modules\Offer\Models\OfferCategory;
use Modules\Offer\Notifications\BookingRequestAccepted;
use Modules\Offer\Notifications\BookingRequestCancelled;
use Modules\Offer\Notifications\BookingRequestReceived;
use Modules\Offer\Notifications\BookingRequestRefused;
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

    public function test_notifications_are_filtered_by_type_and_by_home_for_a_request_type(): void
    {
        $user = User::factory()->create(['locale' => 'fr']);
        $furnished = OfferCategory::query()->whereJsonContains('name->fr', 'Location meublée')->first() ?? OfferCategory::query()->create(['name' => ['fr' => 'Location meublée']]);
        $longTerm = OfferCategory::query()->whereJsonContains('name->fr', 'Location longue durée')->first() ?? OfferCategory::query()->create(['name' => ['fr' => 'Location longue durée']]);
        $studio = Offer::factory()->create(['title' => ['fr' => 'Studio photos'], 'category_id' => $furnished->getKey()]);
        $flat = Offer::factory()->create(['title' => ['fr' => 'Appartement Akwa'], 'category_id' => $longTerm->getKey()]);
        $create = fn (string $type, string $title, ?Offer $offer) => $user->notifications()->create([
            'id' => (string) Str::uuid(), 'type' => $type,
            'data' => ['title' => $title, 'body' => 'Texte', ...($offer === null ? [] : ['offer_id' => $offer->getKey()])],
        ]);
        $create(BookingRequestReceived::class, 'Demande studio', $studio);
        $create(BookingRequestAccepted::class, 'Acceptée appartement', $flat);
        $create('Autre', 'Sans logement', null);

        $this->app['auth']->forgetGuards();
        $filters = collect($this->withToken($user->createToken('iPhone')->plainTextToken)->withHeaders(['X-Country' => 'CM', 'Accept-Language' => 'fr'])
            ->getJson('/api/v1/notifications')->json('data.filters'))->keyBy('name');
        $this->assertSame(['user', 'type', 'offer', 'category'], $filters->keys()->all());
        // Le type de logement (la catégorie de l'offre), comme « Logement » : seulement pour un type de demande.
        $this->assertEqualsCanonicalizing([(string) $furnished->getKey() => 'Location meublée', (string) $longTerm->getKey() => 'Location longue durée'], array_column($filters['category']['options'], 'label', 'value'));
        $this->assertSame($filters['offer']['visible_when'], $filters['category']['visible_when']);
        // Les types de ses notifications, nommés par le domaine ; un type inconnu des modules n'est pas proposé.
        $this->assertSame(['Demande de logement en attente', 'Demande de logement acceptée'], array_column($filters['type']['options'], 'label'));
        $this->assertSame([BookingRequestReceived::class, BookingRequestAccepted::class], array_column($filters['type']['options'], 'value'));
        // « Logement » : seulement pour un type de demande, avec une recherche.
        $this->assertSame([BookingRequestReceived::class, BookingRequestAccepted::class, BookingRequestRefused::class, BookingRequestCancelled::class], $filters['offer']['visible_when']['values']);
        $this->assertTrue($filters['offer']['searchable']);
        $this->assertSame(['Appartement Akwa', 'Studio photos'], array_column($filters['offer']['options'], 'label'));

        $this->assertSame(['Acceptée appartement'], $this->titles($user, '?filter[type]='.urlencode(BookingRequestAccepted::class)));
        $this->assertSame(['Demande studio'], $this->titles($user, "?filter[offer]={$studio->getKey()}"));
        $this->assertSame(['Demande studio'], $this->titles($user, "?filter[category]={$furnished->getKey()}"));
        $this->assertSame(['Acceptée appartement'], $this->titles($user, "?filter[category]={$longTerm->getKey()}"));
        // Un type inconnu ne filtre rien.
        $this->assertCount(3, $this->titles($user, '?filter[type]=Inconnu'));
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
