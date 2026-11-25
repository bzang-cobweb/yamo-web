<?php

namespace Modules\Offer\Tests\Feature\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Modules\Auth\Models\User;
use Modules\Location\Models\Country;
use Modules\Offer\Models\Offer;
use Modules\Offer\Models\OfferReview;
use Tests\TestCase;

/**
 * Tous les avis d'une offre en liste, avec la recherche (décision 2026-11-25-liste-avis-generique).
 */
class OfferReviewListingApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['offer.domain' => 'rental']);
    }

    private function list(Offer $offer, string $query = ''): TestResponse
    {
        return $this->withHeaders(['Accept-Language' => 'fr', 'X-Country' => 'CM'])
            ->getJson('/api/v1/lists/offer.reviews?filter[offer]='.$offer->getKey().$query);
    }

    private function offer(): Offer
    {
        $country = Country::query()->where('code', 'CM')->first() ?? Country::factory()->create(['code' => 'CM']);
        $offer = Offer::factory()->for($country)->create(['status' => 'published']);
        foreach ([['Awa', 5, 'Accueil chaleureux.'], ['Paul', 4, 'Bon rapport qualité-prix.'], ['Jean', 5, 'Très propre.']] as [$name, $rating, $comment]) {
            OfferReview::factory()->for($offer)->for(User::factory()->create(['first_name' => $name]))->create(['rating' => $rating, 'comment' => $comment]);
        }

        return $offer;
    }

    public function test_a_visitor_sees_the_reviews_with_the_rating_title_and_the_count(): void
    {
        $offer = $this->offer();
        $data = $this->list($offer)->assertOk()->json('data');

        // « Avis » en titre ; la note et le nombre en tête de la liste (décision 2026-11-25-liste-avis-note-en-tete).
        $this->assertSame('Avis', $data['title']);
        $this->assertSame(['title' => '4,7', 'subtitle' => '3 avis'], $data['heading']);
        $this->assertNotNull($data['search']);
        $this->assertCount(3, $data['items']);
        $item = collect($data['items'])->firstWhere('title', 'Paul');
        $this->assertSame('Bon rapport qualité-prix.', $item['subtitle']);
        $this->assertEquals(['average' => 4, 'count' => 1], $item['rating']);
        // La date formatée par le serveur, la même que sur la fiche (décision 2026-11-25-dates-formatees-serveur).
        $fiche = $this->withHeaders(['Accept-Language' => 'fr', 'X-Country' => 'CM'])->getJson('/api/v1/offers/'.$offer->getKey())->json('data.reviews.latest');
        $this->assertSame(collect($fiche)->firstWhere('author', 'Paul')['date'], $item['detail']);
        $this->assertSame('review', $item['layout']);
    }

    public function test_the_reviews_come_by_pages_of_twenty(): void
    {
        // Défilement infini, comme toute liste : 20 par page, la suite par son curseur.
        $offer = $this->offer();
        OfferReview::factory()->count(20)->for($offer)->create();

        $first = $this->list($offer)->assertOk()->json('data');
        $this->assertCount(20, $first['items']);
        $this->assertNotNull($first['next_cursor']);
        $second = $this->list($offer, '&cursor='.urlencode($first['next_cursor']))->assertOk()->json('data');
        $this->assertCount(3, $second['items']);
        $this->assertNull($second['next_cursor']);
        $this->assertSame([], array_intersect(array_column($first['items'], 'id'), array_column($second['items'], 'id')));
    }

    public function test_the_search_looks_in_the_comment_and_the_first_name(): void
    {
        $offer = $this->offer();

        $this->assertSame(['Paul'], array_column($this->list($offer, '&search=qualité')->json('data.items'), 'title'));
        $this->assertSame(['Jean'], array_column($this->list($offer, '&search=jea')->json('data.items'), 'title'));
        $this->assertSame('1 avis', $this->list($offer, '&search=jea')->json('data.heading.subtitle'));
    }

    public function test_no_offer_or_an_unpublished_one_shows_nothing(): void
    {
        $offer = $this->offer();
        $offer->update(['status' => 'draft']);

        $this->assertSame([], $this->list($offer)->assertOk()->json('data.items'));
        $this->withHeaders(['X-Country' => 'CM'])->getJson('/api/v1/lists/offer.reviews')->assertOk()->assertJsonPath('data.items', []);
    }
}
