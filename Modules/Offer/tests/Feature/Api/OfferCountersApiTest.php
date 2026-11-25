<?php

namespace Modules\Offer\Tests\Feature\Api;

use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Modules\Auth\Enums\PermissionEnum;
use Modules\Auth\Enums\RoleEnum;
use Modules\Auth\Models\User;
use Modules\Auth\Tests\Feature\Filament\AuthFilamentTestCase;
use Modules\Location\Models\Address;
use Modules\Location\Models\Country;
use Modules\Offer\Filament\Resources\OfferResource\Pages\EditOffer;
use Modules\Offer\Models\Offer;
use Spatie\Permission\Models\Role;

/**
 * Les vues et les clics d'une offre, comme ceux des partenaires (décision 2026-11-25-compteurs-offres) ; le nombre
 * d'offres en tête de la recherche (décision 2026-11-25-recherche-nombre).
 */
class OfferCountersApiTest extends AuthFilamentTestCase
{
    use RefreshDatabase;

    private Country $cameroon;

    private Address $douala;

    protected function setUp(): void
    {
        parent::setUp();

        config(['offer.domain' => 'rental']);
        $this->cameroon = Country::factory()->create(['code' => 'CM', 'currency' => 'XAF']);
        $this->douala = Address::factory()->for($this->cameroon)->create(['city' => 'Douala']);
    }

    private function member(): User
    {
        $user = User::factory()->create(['locale' => 'fr']);
        $user->assignRole(Role::findOrCreate(RoleEnum::MEMBER->value, 'web'));

        return $user;
    }

    /** @param  array<string, mixed>  $attributes */
    private function offer(array $attributes = []): Offer
    {
        return Offer::factory()->at($this->douala)->create(['status' => 'published', ...$attributes]);
    }

    private function api(?User $user, string $method, string $uri)
    {
        // Le garde Sanctum garde le compte d'une requête à l'autre dans un même test : chaque appel repart de zéro.
        $this->app['auth']->forgetGuards();
        $request = $this->withHeaders(['Accept-Language' => 'fr', 'X-Country' => 'CM']);
        if ($user !== null) {
            $request = $request->withToken($user->createToken('iPhone')->plainTextToken);
        }

        return $request->json($method, $uri);
    }

    /** @return array{0: int, 1: int} */
    private function counters(Offer $offer): array
    {
        $fresh = $offer->fresh();

        return [(int) $fresh->views_count, (int) $fresh->clicks_count];
    }

    public function test_the_search_shows_how_many_offers_it_found(): void
    {
        $this->offer(['title' => ['fr' => 'Studio à Akwa']]);
        $this->offer(['title' => ['fr' => 'Villa à Bonapriso']]);
        $this->offer(['status' => 'draft']);

        $this->assertSame(['title' => '2 logements', 'subtitle' => null], $this->api(null, 'GET', '/api/v1/lists/offer.browse')->assertOk()->json('data.heading'));
        $this->assertSame('1 logement', $this->api(null, 'GET', '/api/v1/lists/offer.browse?search=Akwa')->json('data.heading.title'));
        $this->assertSame('Aucun logement', $this->api(null, 'GET', '/api/v1/lists/offer.browse?search=Kribi')->json('data.heading.title'));
    }

    public function test_the_search_and_the_home_count_a_view_except_for_the_owner(): void
    {
        $owner = $this->member();
        $mine = $this->offer(['user_id' => $owner->getKey()]);
        $other = $this->offer();
        $draft = $this->offer(['status' => 'draft']);

        // La recherche, puis l'accueil : les villes et « À la une » (2 offres, les deux y sont).
        $this->api(null, 'GET', '/api/v1/lists/offer.browse')->assertOk();
        $this->api(null, 'GET', '/api/v1/home')->assertOk();
        $this->assertSame([3, 0], $this->counters($other));
        $this->assertSame([3, 0], $this->counters($mine));
        $this->assertSame([0, 0], $this->counters($draft));

        // Son propriétaire ne compte pas de vue pour elle.
        $this->api($owner, 'GET', '/api/v1/lists/offer.browse')->assertOk();
        $this->api($owner, 'GET', '/api/v1/home')->assertOk();
        $this->assertSame([3, 0], $this->counters($mine));
        $this->assertSame([6, 0], $this->counters($other));
    }

    public function test_a_touched_card_counts_a_click_except_for_the_owner_and_the_detail_counts_nothing(): void
    {
        $owner = $this->member();
        $offer = $this->offer(['user_id' => $owner->getKey()]);
        $draft = $this->offer(['status' => 'draft', 'user_id' => $owner->getKey()]);

        $this->api(null, 'POST', "/api/v1/offers/{$offer->getKey()}/click")->assertNoContent();
        $this->api($this->member(), 'POST', "/api/v1/offers/{$offer->getKey()}/click")->assertNoContent();
        $this->api($owner, 'POST', "/api/v1/offers/{$offer->getKey()}/click")->assertNoContent();
        $this->assertSame([0, 2], $this->counters($offer));

        // Une offre que les autres ne voient pas : 404, rien compté.
        $this->api(null, 'POST', "/api/v1/offers/{$draft->getKey()}/click")->assertNotFound();
        $this->assertSame([0, 0], $this->counters($draft));

        // La fiche, ouverte autrement (lien direct, « Mes logements »), ne compte rien.
        $this->api(null, 'GET', "/api/v1/offers/{$offer->getKey()}")->assertOk();
        $this->assertSame([0, 2], $this->counters($offer));
    }

    public function test_my_homes_show_their_views(): void
    {
        $owner = $this->member();
        $this->offer(['user_id' => $owner->getKey(), 'views_count' => 12]);

        $item = $this->api($owner, 'GET', '/api/v1/lists/offer.mine')->assertOk()->json('data.items.0');
        // Un œil et le nombre (décision 2026-11-25-vues-oeil).
        $this->assertSame(['count' => 12, 'label' => '12 vues'], $item['views']);
        $this->assertArrayNotHasKey('detail', $item);
    }

    public function test_the_back_office_shows_the_counters_in_a_statistics_tab_without_changing_them(): void
    {
        Filament::setServingStatus();
        $this->actingAsUserWith(PermissionEnum::VIEW_OFFERS, PermissionEnum::UPDATE_OFFERS);
        $offer = $this->offer(['views_count' => 12, 'clicks_count' => 3]);

        Livewire::test(EditOffer::class, ['record' => $offer->getKey()])
            ->assertSee('Statistiques')
            ->assertSchemaStateSet(['views_count' => 12, 'clicks_count' => 3])
            // Les champs obligatoires du domaine, que la fabrique ne remplit pas ; les compteurs, eux, ne s'enregistrent pas.
            ->fillForm(['details.property_type' => 'studio', 'price' => '50000', 'details.bedrooms' => '1', 'views_count' => 99, 'clicks_count' => 99])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame([12, 3], $this->counters($offer));
    }
}
