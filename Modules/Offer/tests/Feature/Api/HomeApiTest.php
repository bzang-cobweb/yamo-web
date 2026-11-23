<?php

namespace Modules\Offer\Tests\Feature\Api;

use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Location\Models\Address;
use Modules\Location\Models\Country;
use Modules\Offer\Models\Offer;
use Tests\TestCase;

/**
 * L'accueil de l'app : un carrousel par ville, 10 offres visibles au hasard (décision
 * 2026-11-23-accueil-carrousels-par-ville).
 */
class HomeApiTest extends TestCase
{
    use RefreshDatabase;

    private Country $cameroon;

    protected function setUp(): void
    {
        parent::setUp();

        config(['offer.domain' => 'rental']);
        $this->cameroon = Country::factory()->create(['code' => 'CM', 'currency' => 'XAF']);
    }

    private function home()
    {
        return $this->withHeaders(['Accept-Language' => 'fr', 'X-Country' => 'CM'])->getJson('/api/v1/home');
    }

    public function test_one_carousel_per_city_with_ten_random_visible_offers_at_most(): void
    {
        $douala = Address::factory()->for($this->cameroon)->create(['city' => 'Douala']);
        $yaounde = Address::factory()->for($this->cameroon)->create(['city' => 'Yaoundé']);
        $kribi = Address::factory()->for($this->cameroon)->create(['city' => 'Kribi']);
        $doualaOffers = Offer::factory()->count(12)->at($douala)->create(['status' => 'published']);
        $yaoundeOffer = Offer::factory()->at($yaounde)->create(['status' => 'published']);
        // Ni brouillon ni archive : Kribi n'a pas de carrousel.
        Offer::factory()->at($kribi)->create(['status' => 'draft']);
        Offer::factory()->at($kribi)->create(['status' => 'archived']);
        // Un autre pays ne s'affiche pas.
        $senegal = Country::factory()->create(['code' => 'SN']);
        Offer::factory()->at(Address::factory()->for($senegal)->create(['city' => 'Dakar']))->create(['status' => 'published', 'country_id' => $senegal->getKey()]);

        $carousels = $this->home()->assertOk()->json('data.carousels');

        // Les villes par ordre alphabétique.
        $this->assertSame(['Douala', 'Yaoundé'], array_column($carousels, 'city'));
        // 10 au plus, toutes de la ville, chacune une fois ; en cartes comme la recherche.
        $ids = array_column($carousels[0]['items'], 'id');
        $this->assertCount(10, $ids);
        $this->assertSame($ids, array_values(array_unique($ids)));
        $this->assertEmpty(array_diff($ids, $doualaOffers->map(fn (Offer $offer): string => (string) $offer->getKey())->all()));
        $this->assertSame([(string) $yaoundeOffer->getKey()], array_column($carousels[1]['items'], 'id'));
        $this->assertSame('/offers/'.$yaoundeOffer->getKey(), $carousels[1]['items'][0]['link']);
        $this->assertArrayHasKey('image', $carousels[1]['items'][0]);
    }

    public function test_the_draw_changes_between_calls(): void
    {
        $douala = Address::factory()->for($this->cameroon)->create(['city' => 'Douala']);
        Offer::factory()->count(30)->at($douala)->create(['status' => 'published']);

        // Au hasard : sur plusieurs appels, le tirage change au moins une fois.
        $draws = collect(range(1, 5))->map(fn (): string => implode(',', array_column($this->home()->json('data.carousels.0.items'), 'id')));
        $this->assertGreaterThan(1, $draws->unique()->count());
    }

    public function test_as_many_offers_per_city_as_set_in_the_application(): void
    {
        // Décision 2026-11-23-accueil-nombres-reglables.
        $douala = Address::factory()->for($this->cameroon)->create(['city' => 'Douala']);
        Offer::factory()->count(6)->at($douala)->create(['status' => 'published']);
        Setting::current()->update(['home_offers_per_city' => 4]);

        $this->assertCount(4, $this->home()->json('data.carousels.0.items'));
    }

    public function test_no_city_no_carousel(): void
    {
        $this->home()->assertOk()->assertExactJson(['data' => ['carousels' => []]]);
    }
}
