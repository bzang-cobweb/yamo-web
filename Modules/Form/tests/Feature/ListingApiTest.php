<?php

namespace Modules\Form\Tests\Feature;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Modules\Auth\Models\User;
use Modules\Form\Enums\SuccessAction;
use Modules\Form\Support\Listing;
use Modules\Form\Support\ListingRegistry;
use Modules\Form\Support\ListItem;
use Modules\Form\Support\Submission;
use Tests\TestCase;

/**
 * Une liste déclarée sur le serveur seul est servie à l'app, avec ce qu'elle
 * décrit (décision 2026-11-18-adresses-liste-generique).
 */
class ListingApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_new_list_is_served_without_anything_else(): void
    {
        Route::middleware(['api', 'auth:sanctum'])->prefix('api')->name('api.')
            ->delete('v1/test/cards/{card}', fn () => response()->noContent())->name('v1.test.cards.destroy');
        Route::getRoutes()->refreshNameLookups();
        app(ListingRegistry::class)->register(CardListing::class);

        $user = User::factory()->create(['name' => 'Visa']);

        $this->withToken($user->createToken('iPhone')->plainTextToken)
            ->getJson('/api/v1/lists/test.cards')
            ->assertOk()
            ->assertExactJson(['data' => [
                'name' => 'test.cards',
                'title' => 'Cartes',
                'empty' => null,
                'create' => null,
                'search' => null,
                'next_cursor' => null,
                'items' => [[
                    'id' => (string) $user->getKey(),
                    'title' => 'Visa',
                    'subtitle' => null,
                    'image' => null,
                    'edit' => null,
                    'delete' => [
                        'label' => 'Retirer', 'confirm' => 'Sûr ?', 'method' => 'DELETE', 'endpoint' => "/v1/test/cards/{$user->getKey()}",
                        'destructive' => true, 'success' => ['action' => 'back', 'message' => 'Retirée.'],
                    ],
                ]],
            ]]);
    }

    public function test_items_are_served_by_pages_following_the_cursor(): void
    {
        app(ListingRegistry::class)->register(PagedListing::class);
        $users = User::factory()->count(5)->create();
        $this->withToken($users->first()->createToken('iPhone')->plainTextToken);

        $seen = [];
        $cursor = null;
        $pages = 0;

        do {
            $data = $this->getJson('/api/v1/lists/test.paged'.($cursor === null ? '' : '?cursor='.urlencode($cursor)))->assertOk()->json('data');
            $this->assertSame('Pages', $data['title']);
            $seen = [...$seen, ...array_column($data['items'], 'id')];
            $cursor = $data['next_cursor'];
            $pages++;
        } while ($cursor !== null && $pages < 10);

        $this->assertSame(3, $pages);
        $this->assertSame($users->map(fn (User $user): string => (string) $user->getKey())->all(), $seen);
    }

    public function test_removing_an_item_between_pages_neither_skips_nor_repeats(): void
    {
        app(ListingRegistry::class)->register(PagedListing::class);
        $users = User::factory()->count(5)->create();
        $this->withToken($users->last()->createToken('iPhone')->plainTextToken);

        $first = $this->getJson('/api/v1/lists/test.paged')->json('data');
        $users[0]->forceDelete();
        $second = $this->getJson('/api/v1/lists/test.paged?cursor='.urlencode($first['next_cursor']))->json('data');

        $this->assertSame([(string) $users[2]->getKey(), (string) $users[3]->getKey()], array_column($second['items'], 'id'));
    }

    public function test_an_invalid_cursor_serves_the_first_page(): void
    {
        app(ListingRegistry::class)->register(PagedListing::class);
        $users = User::factory()->count(3)->create();
        $this->withToken($users->first()->createToken('iPhone')->plainTextToken);
        $firstIds = [(string) $users[0]->getKey(), (string) $users[1]->getKey()];

        foreach ([
            'garbage',
            base64_encode('"text"'),
            base64_encode(json_encode(['nope' => 3, '_pointsToNextItems' => true])),
            base64_encode(json_encode(['_pointsToNextItems' => true])),
        ] as $cursor) {
            $this->getJson('/api/v1/lists/test.paged?cursor='.urlencode($cursor))
                ->assertOk()
                ->assertJsonPath('data.items.*.id', $firstIds);
        }

        $this->getJson('/api/v1/lists/test.paged?cursor[]=x')->assertOk()->assertJsonPath('data.items.*.id', $firstIds);
    }

    public function test_a_list_without_search_ignores_it(): void
    {
        app(ListingRegistry::class)->register(PagedListing::class);
        $users = User::factory()->count(2)->create();
        $this->withToken($users->first()->createToken('iPhone')->plainTextToken);

        $this->getJson('/api/v1/lists/test.paged?search=nothing-matches')
            ->assertOk()
            ->assertJsonPath('data.search', null)
            ->assertJsonCount(2, 'data.items');
    }

    public function test_a_list_refused_to_the_account_is_forbidden(): void
    {
        app(ListingRegistry::class)->register(AdminOnlyListing::class);

        $this->withToken(User::factory()->create()->createToken('iPhone')->plainTextToken)
            ->getJson('/api/v1/lists/test.admin')
            ->assertForbidden();
    }
}

/** Liste de test : en lecture seule, un item retirable. */
class CardListing extends Listing
{
    public static function name(): string
    {
        return 'test.cards';
    }

    public function title(): string
    {
        return 'Cartes';
    }

    public function query(): Builder
    {
        return User::query()->orderBy('id');
    }

    public function item(Model $record): ListItem
    {
        return ListItem::make($record->getKey(), (string) $record->getAttribute('name'))->delete('Retirer', 'Sûr ?', Submission::toRoute(
            'api.v1.test.cards.destroy', SuccessAction::BACK, 'Retirée.', destructive: true, parameters: ['card' => $record->getKey()],
        ));
    }
}

/** Liste de test refusée à tout compte. */
class AdminOnlyListing extends Listing
{
    public static function name(): string
    {
        return 'test.admin';
    }

    public function title(): string
    {
        return 'Admin';
    }

    public function query(): Builder
    {
        return User::query()->orderBy('id');
    }

    public function item(Model $record): ListItem
    {
        return ListItem::make($record->getKey(), 'x');
    }

    public function canBeViewedBy(?Authenticatable $user): bool
    {
        return false;
    }
}

/** Liste de test paginée par deux. */
class PagedListing extends Listing
{
    public static function name(): string
    {
        return 'test.paged';
    }

    public function title(): string
    {
        return 'Pages';
    }

    public function perPage(): int
    {
        return 2;
    }

    public function query(): Builder
    {
        return User::query()->orderBy('id');
    }

    public function item(Model $record): ListItem
    {
        return ListItem::make($record->getKey(), (string) $record->getAttribute('name'));
    }
}
