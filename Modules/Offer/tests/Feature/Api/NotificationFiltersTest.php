<?php

namespace Modules\Offer\Tests\Feature\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Modules\Auth\Models\User;
use Modules\Location\Models\Country;
use Modules\Offer\Database\Seeders\OfferDatabaseSeeder;
use Modules\Offer\Models\Offer;
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

    public function test_notifications_are_filtered_by_home_and_request_status(): void
    {
        $user = User::factory()->create(['locale' => 'fr']);
        $published = Offer::factory()->create(['status' => 'published', 'title' => ['fr' => 'Studio photos']]);
        $draft = Offer::factory()->create(['status' => 'draft', 'title' => ['fr' => 'Appartement Akwa']]);
        $this->notify($user, 'Pour le studio', $published, 'pending');
        $this->notify($user, 'Pour l’appartement', $draft, 'accepted');
        $this->notify($user, 'Sans logement', null);

        $this->app['auth']->forgetGuards();
        $filters = $this->withToken($user->createToken('iPhone')->plainTextToken)->withHeaders(['X-Country' => 'CM', 'Accept-Language' => 'fr'])
            ->getJson('/api/v1/notifications')->json('data.filters');
        $this->assertSame(['user', 'offer', 'status'], array_column($filters, 'name'));
        $this->assertSame(['Membre', 'Logement', 'Statut'], array_column($filters, 'label'));
        $this->assertSame(['pending', 'accepted', 'refused', 'cancelled'], array_column($filters[2]['options'], 'value'));
        // Membre et logement : une recherche dans la liste, même courte.
        $this->assertSame([true, true, false], array_column($filters, 'searchable'));
        $this->assertSame(['Appartement Akwa', 'Studio photos'], array_column($filters[1]['options'], 'label'));

        $this->assertCount(3, $this->titles($user));
        $this->assertSame(['Pour le studio'], $this->titles($user, "?filter[offer]={$published->getKey()}"));
        $this->assertSame(['Pour l’appartement'], $this->titles($user, '?filter[status]=accepted'));
        $this->assertSame([], $this->titles($user, "?filter[offer]={$published->getKey()}&filter[status]=accepted"));
        // Une valeur inconnue ne filtre rien.
        $this->assertCount(3, $this->titles($user, '?filter[status]=inconnu'));
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
        $this->assertSame(['Awa Ngono', 'Paul Etoa'], array_column($filters[0]['options'], 'label'));

        $this->assertSame(['De Paul'], $this->titles($user, "?filter[user]={$paul->getKey()}"));
    }
}
