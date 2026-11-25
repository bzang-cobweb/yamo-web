<?php

namespace Modules\Offer\Tests\Feature\Api;

use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Location\Models\Address;
use Modules\Location\Models\Country;
use Modules\Location\Models\Region;
use Modules\Offer\Models\Offer;
use Modules\Offer\Models\OfferReview;
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

        // Une par région, dans l'ordre du back-office (décision 2026-11-25-region-de-l-adresse) : ici, la fabrique
        // range chaque ville dans une région de son nom, créée dans l'ordre ; « Voir tout » filtre sur la région.
        $this->assertSame(['Douala', 'Yaoundé'], array_column($carousels, 'title'));
        $this->assertSame(['region' => (string) $douala->region_id], $carousels[0]['filter']);
        // 10 au plus, toutes de la ville, chacune une fois ; en cartes comme la recherche.
        $ids = array_column($carousels[0]['items'], 'id');
        $this->assertCount(10, $ids);
        $this->assertSame($ids, array_values(array_unique($ids)));
        $this->assertEmpty(array_diff($ids, $doualaOffers->map(fn (Offer $offer): string => (string) $offer->getKey())->all()));
        $this->assertSame([(string) $yaoundeOffer->getKey()], array_column($carousels[1]['items'], 'id'));
        $this->assertSame('/offers/'.$yaoundeOffer->getKey(), $carousels[1]['items'][0]['link']);
        $this->assertArrayHasKey('image', $carousels[1]['items'][0]);
    }

    public function test_one_carousel_per_region_with_the_offers_of_all_its_cities(): void
    {
        // Décision 2026-11-25-region-de-l-adresse : dans l'ordre du back-office.
        $littoral = Region::factory()->create(['country_id' => $this->cameroon->getKey(), 'name' => ['fr' => 'Littoral', 'en' => 'Littoral'], 'position' => 2]);
        $centre = Region::factory()->create(['country_id' => $this->cameroon->getKey(), 'name' => ['fr' => 'Centre', 'en' => 'Centre'], 'position' => 1]);
        Offer::factory()->at(Address::factory()->for($this->cameroon)->create(['city' => 'Douala', 'region_id' => $littoral->getKey()]))->create(['status' => 'published']);
        Offer::factory()->at(Address::factory()->for($this->cameroon)->create(['city' => 'Edéa', 'region_id' => $littoral->getKey()]))->create(['status' => 'published']);
        Offer::factory()->at(Address::factory()->for($this->cameroon)->create(['city' => 'Yaoundé', 'region_id' => $centre->getKey()]))->create(['status' => 'published']);

        $carousels = $this->home()->assertOk()->json('data.carousels');

        $this->assertSame(['Centre', 'Littoral'], array_column($carousels, 'title'));
        $this->assertCount(2, $carousels[1]['items']);
        $this->assertSame(['region' => (string) $littoral->getKey()], $carousels[1]['filter']);
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
        $this->home()->assertOk()->assertExactJson(['data' => ['featured' => [], 'carousels' => []]]);
    }

    /**
     * Une offre publiée à Douala, avec ses avis.
     *
     * @param  array<string, mixed>  $attributes
     * @param  list<int>  $ratings
     */
    private function offer(array $attributes = [], array $ratings = []): Offer
    {
        $offer = Offer::factory()->at(Address::factory()->for($this->cameroon)->create(['city' => 'Douala']))->create(['status' => 'published', ...$attributes]);
        foreach ($ratings as $rating) {
            OfferReview::factory()->for($offer)->create(['rating' => $rating]);
        }

        return $offer;
    }

    /** @return list<string> */
    private function featured(): array
    {
        return array_column($this->home()->assertOk()->json('data.featured'), 'id');
    }

    public function test_featured_block_puts_the_featured_offers_first_then_the_best_rated(): void
    {
        // Décision 2026-11-25-bloc-a-la-une : 2 sans réglage.
        $featured = $this->offer(['featured_until' => now()->addWeek()]);
        $this->offer(['featured_until' => now()->subDay()], [3]);
        $best = $this->offer([], [5, 5]);
        $this->offer([], [4]);
        $this->offer();

        $this->assertSame([(string) $featured->getKey(), (string) $best->getKey()], $this->featured());
        // Des cartes comme celles des villes : la photo à la taille de l'accueil.
        $this->assertArrayHasKey('image', $this->home()->json('data.featured.0'));
    }

    public function test_featured_block_breaks_ties_with_the_most_recent_and_follows_the_setting(): void
    {
        $old = $this->offer(['published_at' => now()->subMonth()], [4]);
        $recent = $this->offer(['published_at' => now()->subDay()], [4]);
        $unrated = $this->offer(['published_at' => now()]);
        $this->offer(['published_at' => now()->subYear()]);
        Setting::current()->update(['home_featured' => 3]);

        $this->assertSame([(string) $recent->getKey(), (string) $old->getKey(), (string) $unrated->getKey()], $this->featured());
    }

    public function test_featured_block_draws_the_featured_offers_at_random(): void
    {
        foreach (range(1, 8) as $ignored) {
            $this->offer(['featured_until' => now()->addWeek()]);
        }

        $draws = collect(range(1, 6))->map(fn (): string => implode(',', $this->featured()));
        $this->assertGreaterThan(1, $draws->unique()->count());
        $this->assertCount(2, explode(',', (string) $draws->first()));
    }

    public function test_with_payment_the_featured_block_shows_only_featured_offers(): void
    {
        Setting::current()->update(['payment_required' => true, 'home_featured' => 4]);
        $paid = ['starts_at' => now()->subDay(), 'expires_at' => now()->addMonth()];
        $featured = $this->offer([...$paid, 'featured_until' => now()->addWeek()]);
        $this->offer($paid, [5]);

        $this->assertSame([(string) $featured->getKey()], $this->featured());

        $featured->update(['featured_until' => null]);
        $this->assertSame([], $this->featured());
    }
}
