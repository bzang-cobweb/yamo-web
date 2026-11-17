<?php

namespace Modules\Form\Tests\Feature;

use Illuminate\Contracts\Auth\Authenticatable;
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

        $user = User::factory()->create();

        $this->withToken($user->createToken('iPhone')->plainTextToken)
            ->getJson('/api/v1/lists/test.cards')
            ->assertOk()
            ->assertExactJson(['data' => [
                'name' => 'test.cards',
                'title' => 'Cartes',
                'empty' => null,
                'create' => null,
                'items' => [[
                    'id' => '7',
                    'title' => 'Visa',
                    'subtitle' => null,
                    'edit' => null,
                    'delete' => [
                        'label' => 'Retirer', 'confirm' => 'Sûr ?', 'method' => 'DELETE', 'endpoint' => '/v1/test/cards/7',
                        'destructive' => true, 'success' => ['action' => 'back', 'message' => 'Retirée.'],
                    ],
                ]],
            ]]);
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

    public function items(): array
    {
        return [ListItem::make(7, 'Visa')->delete('Retirer', 'Sûr ?', Submission::toRoute(
            'api.v1.test.cards.destroy', SuccessAction::BACK, 'Retirée.', destructive: true, parameters: ['card' => 7],
        ))];
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

    public function items(): array
    {
        return [];
    }

    public function canBeViewedBy(?Authenticatable $user): bool
    {
        return false;
    }
}
