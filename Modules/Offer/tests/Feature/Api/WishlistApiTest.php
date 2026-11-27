<?php

namespace Modules\Offer\Tests\Feature\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Testing\TestResponse;
use Modules\Auth\Enums\RoleEnum;
use Modules\Auth\Models\Role;
use Modules\Auth\Models\User;
use Modules\Location\Models\Country;
use Modules\Offer\Database\Seeders\OfferDatabaseSeeder;
use Modules\Offer\Enums\WishlistKindEnum;
use Modules\Offer\Models\Offer;
use Modules\Offer\Models\Wishlist;
use Modules\Offer\Models\WishlistItem;
use Modules\Offer\Services\WishlistService;
use Tests\TestCase;

/**
 * Les listes de favoris nommées et « Récemment consultés » (décision 2026-11-27-listes-de-favoris).
 */
class WishlistApiTest extends TestCase
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

    private function list(User $user, Wishlist $list): TestResponse
    {
        return $this->api($user, 'GET', '/api/v1/lists/offer.wishlist?filter[wishlist]='.$list->getKey());
    }

    public function test_the_heart_panel_lists_named_lists_and_saves_into_a_chosen_or_new_list(): void
    {
        $offer = Offer::factory()->create(['status' => 'published']);
        $other = Offer::factory()->create(['status' => 'published']);
        $member = $this->member();
        $endpoint = "/api/v1/offers/{$offer->getKey()}/favorite";
        $summer = app(WishlistService::class)->add($member, $other, name: 'Été');
        // « Récemment consultés » n'apparaît pas dans le panneau.
        app(WishlistService::class)->recordView($member, $other);

        $panel = $this->api($member, 'GET', $endpoint)->assertOk()->json('data');
        $this->assertSame('Enregistrer dans une liste', $panel['title']);
        $this->assertSame(['label' => 'Créer une liste', 'placeholder' => 'Nom de la liste', 'submit' => 'Créer', 'max_length' => 60], $panel['create']);
        $this->assertSame([['id' => (string) $summer->getKey(), 'name' => 'Été', 'subtitle' => '1 offre']], array_map(fn (array $list): array => array_diff_key($list, ['image' => true]), $panel['lists']));

        // Une liste choisie.
        $this->api($member, 'PUT', $endpoint, ['wishlist' => $summer->getKey()])->assertNoContent();
        $this->assertSame([(string) $offer->getKey(), (string) $other->getKey()], array_column($this->list($member, $summer)->json('data.items'), 'id'));

        // Une nouvelle liste, créée à la volée : l'offre y est rangée.
        $this->api($member, 'PUT', $endpoint, ['name' => '  Week-end  '])->assertNoContent();
        $weekend = Wishlist::query()->where('name', 'Week-end')->sole();
        $this->assertSame([(string) $offer->getKey()], array_column($this->list($member, $weekend)->json('data.items'), 'id'));
        $this->assertSame(['Week-end', 'Été'], array_column($this->api($member, 'GET', $endpoint)->json('data.lists'), 'name'));

        // Le cœur plein la retire de toutes ses listes nommées ; « Récemment consultés » garde les siennes.
        $this->api($member, 'DELETE', $endpoint)->assertNoContent();
        $this->assertSame(0, WishlistItem::query()->where('offer_id', $offer->getKey())->count());
        $this->assertSame(1, app(WishlistService::class)->recentList($member)?->items()->count());
    }

    public function test_saving_refuses_another_members_list_the_recent_list_and_a_too_long_name(): void
    {
        $offer = Offer::factory()->create(['status' => 'published']);
        $member = $this->member();
        $stranger = $this->member();
        $theirs = app(WishlistService::class)->add($stranger, Offer::factory()->create(['status' => 'published']), name: 'À moi');
        $recent = app(WishlistService::class)->ensureRecentList($member);
        $endpoint = "/api/v1/offers/{$offer->getKey()}/favorite";

        $this->api($member, 'PUT', $endpoint, ['wishlist' => $theirs->getKey()])->assertUnprocessable()->assertJsonValidationErrors('wishlist');
        $this->api($member, 'PUT', $endpoint, ['wishlist' => $recent->getKey()])->assertUnprocessable()->assertJsonValidationErrors('wishlist');
        $this->api($member, 'PUT', $endpoint, ['name' => str_repeat('a', 61)])->assertUnprocessable()->assertJsonValidationErrors('name');
        $this->api(null, 'GET', $endpoint)->assertUnauthorized();
        $this->assertSame(0, WishlistItem::query()->where('offer_id', $offer->getKey())->count());
    }

    public function test_my_favorites_lists_recent_first_then_named_lists_with_count_photo_and_link(): void
    {
        $member = $this->member();
        $service = app(WishlistService::class);
        $first = $service->add($member, Offer::factory()->create(['status' => 'published']));
        $second = $service->add($member, Offer::factory()->create(['status' => 'published']), name: 'Vacances');
        $service->add($member, Offer::factory()->create(['status' => 'published']), $second->getKey());

        $data = $this->api($member, 'GET', '/api/v1/lists/offer.wishlists')->assertOk()->json('data');
        $this->assertSame('Mes favoris', $data['title']);
        // « Récemment consultés », toujours là, puis la liste la plus récente.
        $recent = $service->recentList($member);
        $this->assertNotNull($recent);
        $this->assertSame(
            [[(string) $recent->getKey(), 'Récemment consultés', 'Aucune offre'], [(string) $second->getKey(), 'Vacances', '2 offres'], [(string) $first->getKey(), 'Favoris', '1 offre']],
            array_map(fn (array $item): array => [$item['id'], $item['title'], $item['subtitle']], $data['items']),
        );
        $this->assertSame('/wishlists/'.$second->getKey(), $data['items'][1]['link']);
        $this->assertSame('side', $data['items'][1]['image_layout']);
        // Rejouée, la liste ne crée pas un second « Récemment consultés ».
        $this->api($member, 'GET', '/api/v1/lists/offer.wishlists')->assertOk();
        $this->assertSame(1, Wishlist::query()->where('kind', WishlistKindEnum::RECENT->value)->count());
    }

    public function test_an_open_named_list_has_rename_and_delete_in_its_top_bar_menu(): void
    {
        $member = $this->member();
        $list = app(WishlistService::class)->add($member, Offer::factory()->create(['status' => 'published']), name: 'Été');

        $data = $this->list($member, $list)->assertOk()->json('data');
        $this->assertSame('Été', $data['title']);
        $this->assertArrayNotHasKey('columns', $data);
        $this->assertSame([
            ['label' => 'Renommer', 'style' => 'default', 'confirm' => null, 'submission' => null, 'form' => ['form' => 'offer.wishlist', 'record' => (string) $list->getKey()], 'link' => null],
            ['label' => 'Supprimer', 'style' => 'destructive', 'confirm' => 'Supprimer la liste « Été » ?', 'submission' => [
                'method' => 'DELETE', 'endpoint' => '/v1/wishlists/'.$list->getKey(), 'destructive' => true, 'success' => ['action' => 'back', 'message' => 'Liste supprimée.'],
            ], 'form' => null, 'link' => null],
        ], $data['header_actions']);

        // « Renommer » : le formulaire servi, son nom rempli, puis l'envoi.
        $form = $this->api($member, 'GET', '/api/v1/forms/offer.wishlist?record='.$list->getKey())->assertOk()->json('data');
        $this->assertSame('Renommer la liste', $form['navigation_title']);
        $this->assertSame('Été', $form['fields'][0]['default']);
        $this->assertSame(['PATCH', '/v1/wishlists/'.$list->getKey()], [$form['submit']['method'], $form['submit']['endpoint']]);
        $this->api($member, 'PATCH', '/api/v1/wishlists/'.$list->getKey(), ['name' => ''])->assertUnprocessable()->assertJsonValidationErrors('name');
        $this->api($member, 'PATCH', '/api/v1/wishlists/'.$list->getKey(), ['name' => 'Hiver'])->assertNoContent();
        $this->assertSame('Hiver', $list->fresh()?->name);

        // « Supprimer » : la liste et ses rangements partent, les offres restent.
        $this->api($member, 'DELETE', '/api/v1/wishlists/'.$list->getKey())->assertNoContent();
        $this->assertNull($list->fresh());
        $this->assertSame(0, WishlistItem::query()->count());
        $this->assertSame(1, Offer::query()->count());
    }

    public function test_nobody_else_sees_renames_or_deletes_my_list_and_the_recent_list_is_kept(): void
    {
        $member = $this->member();
        $stranger = $this->member();
        $list = app(WishlistService::class)->add($member, Offer::factory()->create(['status' => 'published']), name: 'Été');
        $recent = app(WishlistService::class)->ensureRecentList($member);

        $this->assertSame([], $this->list($stranger, $list)->assertOk()->json('data.items'));
        $this->api($stranger, 'PATCH', '/api/v1/wishlists/'.$list->getKey(), ['name' => 'Pirate'])->assertNotFound();
        $this->api($stranger, 'DELETE', '/api/v1/wishlists/'.$list->getKey())->assertNotFound();
        $this->api($stranger, 'GET', '/api/v1/forms/offer.wishlist?record='.$list->getKey())->assertNotFound();
        $this->api($member, 'PATCH', '/api/v1/wishlists/'.$recent->getKey(), ['name' => 'Autre'])->assertForbidden();
        $this->api($member, 'DELETE', '/api/v1/wishlists/'.$recent->getKey())->assertForbidden();
        $this->assertSame('Été', $list->fresh()?->name);
        $this->assertNotNull($recent->fresh());
    }

    public function test_an_opened_offer_enters_recently_viewed_first_and_its_cross_removes_it(): void
    {
        $member = $this->member();
        $own = Offer::factory()->for($member)->create(['status' => 'published']);
        $first = Offer::factory()->create(['status' => 'published']);
        $second = Offer::factory()->create(['status' => 'published']);

        $this->api(null, 'POST', "/api/v1/offers/{$first->getKey()}/click")->assertNoContent();
        $this->assertSame(0, Wishlist::query()->count());

        $this->api($member, 'POST', "/api/v1/offers/{$own->getKey()}/click")->assertNoContent();
        $this->api($member, 'POST', "/api/v1/offers/{$first->getKey()}/click")->assertNoContent();
        $this->travel(1)->minutes();
        $this->api($member, 'POST', "/api/v1/offers/{$second->getKey()}/click")->assertNoContent();
        $this->travel(1)->minutes();
        // Rouverte : elle repasse en tête, une seule fois.
        $this->api($member, 'POST', "/api/v1/offers/{$first->getKey()}/click")->assertNoContent();

        $recent = app(WishlistService::class)->recentList($member);
        $this->assertNotNull($recent);
        $data = $this->list($member, $recent)->assertOk()->json('data');
        $this->assertSame('Récemment consultés', $data['title']);
        $this->assertSame([(string) $first->getKey(), (string) $second->getKey()], array_column($data['items'], 'id'));
        // Deux cartes par ligne, une croix sur la photo, ni cœur ni menu.
        $this->assertSame(2, $data['columns']);
        $this->assertArrayNotHasKey('header_actions', $data);
        $this->assertNull($data['items'][0]['favorite']);
        $delete = $data['items'][0]['delete'];
        $this->assertTrue($delete['on_image']);
        $this->assertSame(['DELETE', "/v1/wishlists/{$recent->getKey()}/offers/{$first->getKey()}"], [$delete['method'], $delete['endpoint']]);

        $this->api($this->member(), 'DELETE', '/api'.$delete['endpoint'])->assertNotFound();
        $this->api($member, 'DELETE', '/api'.$delete['endpoint'])->assertNoContent();
        $this->assertSame([(string) $second->getKey()], array_column($this->list($member, $recent)->json('data.items'), 'id'));
    }

    public function test_recently_viewed_keeps_the_latest_fifty(): void
    {
        $member = $this->member();
        $offers = Offer::factory()->count(Wishlist::RECENT_LIMIT + 2)->create(['status' => 'published']);
        foreach ($offers as $index => $offer) {
            $this->travelTo(now()->addSeconds($index + 1));
            app(WishlistService::class)->recordView($member, $offer);
        }

        $recent = app(WishlistService::class)->recentList($member);
        $this->assertSame(Wishlist::RECENT_LIMIT, $recent?->items()->count());
        $this->assertFalse($recent->items()->whereIn('offer_id', $offers->take(2)->modelKeys())->exists());
    }

    public function test_the_home_block_shows_the_latest_viewed_offers_and_links_to_the_list(): void
    {
        $member = $this->member();
        $this->api($member, 'GET', '/api/v1/wishlists/recent')->assertOk()->assertExactJson(['data' => null]);
        $this->api(null, 'GET', '/api/v1/wishlists/recent')->assertUnauthorized();

        $offers = Offer::factory()->count(12)->create(['status' => 'published']);
        foreach ($offers as $index => $offer) {
            $this->travelTo(now()->addSeconds($index + 1));
            app(WishlistService::class)->recordView($member, $offer);
        }
        $offers->first()?->update(['status' => 'archived']);

        $data = $this->api($member, 'GET', '/api/v1/wishlists/recent')->assertOk()->json('data');
        $recent = app(WishlistService::class)->recentList($member);
        $this->assertSame('Récemment consultés', $data['title']);
        $this->assertSame('/wishlists/'.$recent?->getKey(), $data['link']);
        // Les dix dernières, la dernière ouverte d'abord.
        $this->assertSame($offers->reverse()->take(10)->map(fn (Offer $offer): string => (string) $offer->getKey())->values()->all(), array_column($data['items'], 'id'));
        $this->assertSame('/offers/'.$offers->last()?->getKey(), $data['items'][0]['link']);
    }

    public function test_old_favorites_are_copied_into_a_favorites_list_per_member(): void
    {
        $member = $this->member();
        $english = User::factory()->create(['locale' => 'en']);
        [$one, $two] = Offer::factory()->count(2)->create(['status' => 'published'])->all();
        foreach ([[$member, $one], [$member, $two], [$english, $one]] as [$user, $offer]) {
            DB::table('offer_favorites')->insert(['user_id' => $user->getKey(), 'offer_id' => $offer->getKey(), 'created_at' => now(), 'updated_at' => now()]);
        }

        $migration = require base_path('Modules/Offer/database/migrations/2026_12_12_110000_copy_offer_favorites_to_wishlists.php');
        $migration->up();
        // Rejouable.
        $migration->up();

        $this->assertSame(['Favoris'], Wishlist::query()->ownedBy($member->getKey())->pluck('name')->all());
        $this->assertSame(['Favorites'], Wishlist::query()->ownedBy($english->getKey())->pluck('name')->all());
        $this->assertSame(3, WishlistItem::query()->count());
        $this->assertTrue($this->api($member, 'GET', "/api/v1/offers/{$two->getKey()}")->json('data.favorite.active'));
        // La table d'avant reste.
        $this->assertSame(3, DB::table('offer_favorites')->count());
    }
}
