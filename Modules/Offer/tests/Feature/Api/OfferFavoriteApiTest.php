<?php

namespace Modules\Offer\Tests\Feature\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Modules\Auth\Enums\RoleEnum;
use Modules\Auth\Models\Role;
use Modules\Auth\Models\User;
use Modules\Location\Models\Address;
use Modules\Location\Models\Country;
use Modules\Offer\Database\Seeders\OfferDatabaseSeeder;
use Modules\Offer\Models\Offer;
use Modules\Offer\Models\OfferFavorite;
use Tests\TestCase;

/**
 * Offres en vedette et favoris (décision 2026-11-19-contrat-vedette-favoris).
 */
class OfferFavoriteApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['offer.domain' => 'rental']);
        Country::factory()->create(['code' => 'CM', 'currency' => 'XAF']);
        $this->seed(OfferDatabaseSeeder::class);
    }

    private function member(): User
    {
        $user = User::factory()->create(['locale' => 'fr']);
        $user->assignRole(Role::findOrCreate(RoleEnum::MEMBER->value, 'web'));

        return $user;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function api(?User $user, string $method, string $uri, array $data = []): TestResponse
    {
        app('auth')->forgetGuards();
        $this->flushHeaders();
        $request = $this->withHeaders(['Accept-Language' => 'fr', 'X-Country' => 'CM']);

        if ($user !== null) {
            $request = $request->withToken($user->createToken('iPhone')->plainTextToken);
        }

        return $request->json($method, $uri, $data);
    }

    public function test_featured_offers_come_first_until_their_date_without_badge(): void
    {
        $old = Offer::factory()->create(['status' => 'published']);
        $featured = Offer::factory()->create(['status' => 'published', 'featured_until' => now()->addDays(3)]);
        $recent = Offer::factory()->create(['status' => 'published']);
        $expired = Offer::factory()->create(['status' => 'published', 'featured_until' => now()->subDay()]);

        $items = $this->api(null, 'GET', '/api/v1/lists/offer.browse')->assertOk()->json('data.items');
        $this->assertSame(array_map('strval', [$featured->getKey(), $expired->getKey(), $recent->getKey(), $old->getKey()]), array_column($items, 'id'));
        $this->assertArrayNotHasKey('badge', $items[0]);

        // La date passée, elle reprend sa place.
        $this->travel(4)->days();
        $this->assertSame(array_map('strval', [$expired->getKey(), $recent->getKey(), $featured->getKey(), $old->getKey()]), array_column($this->api(null, 'GET', '/api/v1/lists/offer.browse')->json('data.items'), 'id'));
    }

    public function test_a_featured_offer_is_listed_once_across_pages_and_follows_the_search(): void
    {
        Offer::factory()->count(25)->create(['status' => 'published']);
        $featured = Offer::factory()->create(['status' => 'published', 'featured_until' => now()->addDay(), 'title' => ['fr' => 'Villa en vedette']]);

        $first = $this->api(null, 'GET', '/api/v1/lists/offer.browse')->json('data');
        $second = $this->api(null, 'GET', '/api/v1/lists/offer.browse?cursor='.urlencode($first['next_cursor']))->json('data');
        $ids = [...array_column($first['items'], 'id'), ...array_column($second['items'], 'id')];

        $this->assertSame((string) $featured->getKey(), $ids[0]);
        $this->assertCount(26, $ids);
        $this->assertCount(26, array_unique($ids));
        $this->assertSame([(string) $featured->getKey()], array_column($this->api(null, 'GET', '/api/v1/lists/offer.browse?search=vedette')->json('data.items'), 'id'));
    }

    public function test_beyond_the_pinned_limit_a_featured_offer_stays_in_the_list(): void
    {
        $featured = Offer::factory()->count(22)->sequence(fn ($sequence) => ['featured_until' => now()->addDays(30 - $sequence->index)])->create(['status' => 'published']);
        Offer::factory()->count(5)->create(['status' => 'published']);

        $first = $this->api(null, 'GET', '/api/v1/lists/offer.browse')->json('data');
        $ids = array_column($first['items'], 'id');
        $cursor = $first['next_cursor'];
        while ($cursor !== null) {
            $page = $this->api(null, 'GET', '/api/v1/lists/offer.browse?cursor='.urlencode($cursor))->json('data');
            $ids = [...$ids, ...array_column($page['items'], 'id')];
            $cursor = $page['next_cursor'];
        }

        // 20 vedettes épinglées, puis tout le reste, chacune une fois : aucune perdue.
        $this->assertSame($featured->take(20)->map(fn (Offer $offer): string => (string) $offer->getKey())->all(), array_slice($ids, 0, 20));
        $this->assertCount(27, $ids);
        $this->assertCount(27, array_unique($ids));
    }

    public function test_a_member_adds_and_removes_a_favorite_from_the_list_and_the_detail(): void
    {
        $offer = Offer::factory()->create(['status' => 'published']);
        $member = $this->member();

        $item = $this->api($member, 'GET', '/api/v1/lists/offer.browse')->json('data.items.0');
        $this->assertSame(['active' => false, 'endpoint' => "/v1/offers/{$offer->getKey()}/favorite"], $item['favorite']);
        $this->assertSame("/offers/{$offer->getKey()}", $item['link']);

        $this->api($member, 'PUT', "/api/v1/offers/{$offer->getKey()}/favorite")->assertNoContent();
        $this->api($member, 'PUT', "/api/v1/offers/{$offer->getKey()}/favorite")->assertNoContent();
        $this->assertSame(1, OfferFavorite::query()->count());

        $this->assertTrue($this->api($member, 'GET', '/api/v1/lists/offer.browse')->json('data.items.0.favorite.active'));
        $this->assertTrue($this->api($member, 'GET', "/api/v1/offers/{$offer->getKey()}")->json('data.favorite.active'));
        $favorites = $this->api($member, 'GET', '/api/v1/lists/offer.favorites')->assertOk()->json('data');
        $this->assertSame('Mes favoris', $favorites['title']);
        $this->assertSame([(string) $offer->getKey()], array_column($favorites['items'], 'id'));
        $this->assertTrue($favorites['items'][0]['favorite']['active']);

        $this->api($member, 'DELETE', "/api/v1/offers/{$offer->getKey()}/favorite")->assertNoContent();
        $this->assertSame([], $this->api($member, 'GET', '/api/v1/lists/offer.favorites')->json('data.items'));
        $this->assertFalse($this->api($member, 'GET', "/api/v1/offers/{$offer->getKey()}")->json('data.favorite.active'));
    }

    public function test_no_heart_for_a_visitor_nor_on_ones_own_offer(): void
    {
        $owner = $this->member();
        $offer = Offer::factory()->for($owner)->create(['status' => 'published']);

        $this->assertNull($this->api(null, 'GET', '/api/v1/lists/offer.browse')->json('data.items.0.favorite'));
        $this->assertNull($this->api(null, 'GET', "/api/v1/offers/{$offer->getKey()}")->json('data.favorite'));
        $this->assertNull($this->api($owner, 'GET', '/api/v1/lists/offer.browse')->json('data.items.0.favorite'));
        $this->assertNull($this->api($owner, 'GET', "/api/v1/offers/{$offer->getKey()}")->json('data.favorite'));
        $this->assertNull($this->api($owner, 'GET', '/api/v1/lists/offer.mine')->json('data.items.0.favorite'));
    }

    public function test_the_api_refuses_what_the_app_hides(): void
    {
        $owner = $this->member();
        $own = Offer::factory()->for($owner)->create(['status' => 'published']);
        $draft = Offer::factory()->create(['status' => 'draft']);
        $elsewhere = Offer::factory()->create(['status' => 'published', 'country_id' => Country::factory()->create(['code' => 'SN'])->getKey()]);

        $this->api($owner, 'PUT', "/api/v1/offers/{$own->getKey()}/favorite")->assertForbidden();
        $this->api($owner, 'PUT', "/api/v1/offers/{$draft->getKey()}/favorite")->assertNotFound();
        $this->api($owner, 'PUT', "/api/v1/offers/{$elsewhere->getKey()}/favorite")->assertNotFound();
        $this->api(null, 'PUT', "/api/v1/offers/{$draft->getKey()}/favorite")->assertUnauthorized();
        $this->api(null, 'GET', '/api/v1/lists/offer.favorites')->assertUnauthorized();
        $this->assertSame(0, OfferFavorite::query()->count());
    }

    public function test_an_archived_offer_leaves_my_favorites_and_comes_back_when_republished(): void
    {
        $offer = Offer::factory()->create(['status' => 'published']);
        $member = $this->member();
        $this->api($member, 'PUT', "/api/v1/offers/{$offer->getKey()}/favorite")->assertNoContent();

        $offer->update(['status' => 'archived']);
        $this->assertSame([], $this->api($member, 'GET', '/api/v1/lists/offer.favorites')->json('data.items'));

        $offer->update(['status' => 'published']);
        $this->assertSame([(string) $offer->getKey()], array_column($this->api($member, 'GET', '/api/v1/lists/offer.favorites')->json('data.items'), 'id'));
    }

    public function test_my_favorites_have_the_home_search_and_filters_and_a_search_button(): void
    {
        // Décision 2026-11-19-favoris-recherche-filtres.
        $user = $this->member();
        $douala = Offer::factory()->at(Address::factory()->for(Country::query()->where('code', 'CM')->sole())->create(['city' => 'Douala']))
            ->create(['status' => 'published', 'title' => ['fr' => 'Studio Akwa'], 'price' => '50000', 'details' => ['property_type' => 'studio', 'furnished' => true]]);
        $yaounde = Offer::factory()->at(Address::factory()->for(Country::query()->where('code', 'CM')->sole())->create(['city' => 'Yaoundé']))
            ->create(['status' => 'published', 'title' => ['fr' => 'Maison Bastos'], 'price' => '300000', 'details' => ['property_type' => 'house', 'furnished' => false]]);
        $notMine = Offer::factory()->create(['status' => 'published']);
        foreach ([$douala, $yaounde] as $offer) {
            OfferFavorite::query()->create(['user_id' => $user->getKey(), 'offer_id' => $offer->getKey()]);
        }
        $ids = fn (string $query = ''): array => array_column($this->api($user, 'GET', '/api/v1/lists/offer.favorites'.$query)->json('data.items'), 'id');

        $data = $this->api($user, 'GET', '/api/v1/lists/offer.favorites')->json('data');
        $this->assertNotNull($data['search']);
        $this->assertSame(['label' => 'Rechercher un logement', 'form' => null, 'link' => '/'], $data['footer']);
        $filters = array_column($data['filters'], null, 'name');
        $this->assertSame(['Douala', 'Yaoundé'], array_column($filters['city']['options'], 'value'));

        $this->assertSame([(string) $douala->getKey()], $ids('?filter[city]=Douala'));
        $this->assertSame([(string) $yaounde->getKey()], $ids('?filter[furnished]=false'));
        $this->assertSame([(string) $douala->getKey()], $ids('?filter[price][]=&filter[price][]=100000'));
        $this->assertSame([(string) $yaounde->getKey()], $ids('?search=bastos'));
        $this->assertSame([(string) $yaounde->getKey()], $ids('?search=yaound'));
        $this->assertNotContains((string) $notMine->getKey(), $ids());
    }
}
