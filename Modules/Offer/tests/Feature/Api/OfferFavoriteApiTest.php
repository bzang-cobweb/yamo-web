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

    public function test_an_offer_is_featured_until_its_day_included(): void
    {
        // Décision 2026-11-25-vedette-en-dates : une date, sans heure.
        $today = Offer::factory()->create(['status' => 'published', 'featured_until' => today()]);
        $yesterday = Offer::factory()->create(['status' => 'published', 'featured_until' => today()->subDay()]);

        $this->assertTrue($today->fresh()->isFeatured());
        $this->assertFalse($yesterday->fresh()->isFeatured());
        $this->assertSame([(string) $today->getKey()], array_column(array_slice($this->api(null, 'GET', '/api/v1/lists/offer.browse')->json('data.items'), 0, 1), 'id'));
        $this->assertSame([(int) $today->getKey()], Offer::query()->featured()->pluck('id')->all());
    }

    public function test_every_featured_offer_comes_first_without_limit(): void
    {
        // Décision 2026-11-25-vedettes-d-abord : plus de limite de 20.
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

        // Les 22 vedettes en tête, la date la plus lointaine d'abord, puis le reste, chacune une fois.
        $this->assertSame($featured->map(fn (Offer $offer): string => (string) $offer->getKey())->all(), array_slice($ids, 0, 22));
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
        // Le nombre, sous les filtres (décision 2026-11-22-listes-nombre-en-tete).
        // « 1 favori » (décision 2026-11-25-nombre-favoris).
        $this->assertSame('1 favori', $favorites['heading']['title']);
        // Comme à l'accueil : le titre saisi, la ligne du type dessous (décision 2026-11-21-favoris-titre-saisi-ligne-type).
        // En titre, la ligne du type ; dessous, le titre saisi (décision 2026-11-21-cartes-titre-compose-en-tete).
        $this->assertSame(['Location', $offer->getTranslation('title', 'fr')], [$favorites['items'][0]['title'], $favorites['items'][0]['subtitle']]);

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
            ->create(['status' => 'published', 'title' => ['fr' => 'Studio Akwa'], 'price' => '50000', 'details' => ['property_type' => 'studio', 'rental_type' => 'furnished']]);
        $yaounde = Offer::factory()->at(Address::factory()->for(Country::query()->where('code', 'CM')->sole())->create(['city' => 'Yaoundé']))
            ->create(['status' => 'published', 'title' => ['fr' => 'Maison Bastos'], 'price' => '300000', 'details' => ['property_type' => 'house', 'rental_type' => 'long_term']]);
        $notMine = Offer::factory()->create(['status' => 'published']);
        foreach ([$douala, $yaounde] as $offer) {
            OfferFavorite::query()->create(['user_id' => $user->getKey(), 'offer_id' => $offer->getKey()]);
        }
        $ids = fn (string $query = ''): array => array_column($this->api($user, 'GET', '/api/v1/lists/offer.favorites'.$query)->json('data.items'), 'id');

        $data = $this->api($user, 'GET', '/api/v1/lists/offer.favorites')->json('data');
        $this->assertNotNull($data['search']);
        $this->assertSame(['label' => 'Rechercher un logement', 'form' => null, 'link' => '/search'], $data['footer']);
        $filters = array_column($data['filters'], null, 'name');
        $this->assertSame(['Douala', 'Yaoundé'], array_column($filters['city']['options'], 'value'));

        // La ville se choisit dans une région (décision 2026-11-26-region-ville-en-puces) : sans région, elle est
        // masquée et ignorée.
        $this->assertCount(2, $ids('?filter[city]=Douala'));
        $this->assertSame([(string) $douala->getKey()], $ids('?filter[region][]='.$douala->address->region_id.'&filter[city]=Douala'));
        $this->assertSame([(string) $yaounde->getKey()], $ids('?filter[rental_type][]=long_term'));
        $this->assertSame([(string) $douala->getKey()], $ids('?filter[price][]=&filter[price][]=100000'));
        $this->assertSame([(string) $yaounde->getKey()], $ids('?search=bastos'));
        $this->assertSame([(string) $yaounde->getKey()], $ids('?search=yaound'));
        $this->assertNotContains((string) $notMine->getKey(), $ids());
    }
}
