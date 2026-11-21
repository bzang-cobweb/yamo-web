<?php

namespace Modules\Offer\Tests\Feature\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Location\Models\Address;
use Modules\Location\Models\Country;
use Modules\Offer\Database\Seeders\OfferDatabaseSeeder;
use Modules\Offer\Models\Offer;
use Tests\TestCase;

/**
 * Filtres de la vitrine (décision 2026-11-19-contrat-filtres-vitrine).
 */
class OfferFiltersApiTest extends TestCase
{
    use RefreshDatabase;

    private Country $cameroon;

    protected function setUp(): void
    {
        parent::setUp();

        config(['offer.domain' => 'rental']);
        $this->cameroon = Country::factory()->create(['code' => 'CM', 'currency' => 'XAF']);
        $this->seed(OfferDatabaseSeeder::class);
    }

    /**
     * @param  array<string, mixed>  $details
     */
    private function home(string $city, int $price, array $details, array $overrides = []): Offer
    {
        return Offer::factory()->at(Address::factory()->for($this->cameroon)->create(['city' => $city]))->create([
            'status' => 'published', 'price' => (string) $price, 'currency' => 'XAF',
            'details' => $details,
            ...$overrides,
        ]);
    }

    /**
     * @return list<string>
     */
    private function ids(string $query = ''): array
    {
        return array_column($this->withHeaders(['Accept-Language' => 'fr', 'X-Country' => 'CM'])->getJson('/api/v1/lists/offer.browse'.$query)->assertOk()->json('data.items'), 'id');
    }

    public function test_the_list_describes_its_filters(): void
    {
        $this->home('Yaoundé', 1000, ['property_type' => 'studio']);
        $this->home('Douala', 1000, ['property_type' => 'house']);
        Offer::factory()->at(Address::factory()->for($this->cameroon)->create(['city' => 'Kribi']))->create(['status' => 'draft']);

        $filters = array_column($this->withHeaders(['X-Country' => 'CM', 'Accept-Language' => 'fr'])->getJson('/api/v1/lists/offer.browse')->json('data.filters'), null, 'name');

        $this->assertSame(['city', 'price', 'property_type', 'rental_type', 'bedrooms', 'bathrooms', 'available_from', 'amenities', 'pets', 'smoking', 'parties'], array_keys($filters));
        // « Libre dès le » : sans type de location choisi ou pour un non meublé, pas pour la location meublée
        // (décision 2026-11-22-filtre-libre-des-sans-meuble).
        $this->assertSame(['field' => 'rental_type', 'value' => '', 'values' => ['', 'long_term', 'shared']], $filters['available_from']['visible_when']);
        // Les villes des offres publiées seulement, dans l'ordre.
        $this->assertSame(['Douala', 'Yaoundé'], array_column($filters['city']['options'], 'value'));
        // Un seul intervalle pour le loyer ; le type et les équipements en liste à choix multiples.
        $this->assertSame('range', $filters['price']['type']);
        $this->assertSame('FCFA', $filters['price']['suffix']);
        $this->assertSame('multiselect', $filters['property_type']['type']);
        $this->assertSame('multiselect', $filters['amenities']['type']);
        // Le type de location, en liste (« Tous » ajouté par l'app).
        $this->assertSame('select', $filters['rental_type']['type']);
        $this->assertSame(['long_term', 'furnished', 'shared'], array_column($filters['rental_type']['options'], 'value'));
    }

    public function test_yes_no_rules_filter_on_the_owner_answer(): void
    {
        // Décision 2026-11-19-filtres-regles.
        $filters = array_column($this->withHeaders(['X-Country' => 'CM', 'Accept-Language' => 'fr'])->getJson('/api/v1/lists/offer.browse')->json('data.filters'), null, 'name');
        $this->assertSame('segmented', $filters['pets']['type']);
        $this->assertSame('Animaux acceptés', $filters['pets']['label']);
        $this->assertSame(['Tous', 'Oui', 'Non'], array_column($filters['pets']['options'], 'label'));
        $this->assertSame('any', $filters['pets']['default']);

        $pets = $this->home('Douala', 80000, ['property_type' => 'studio', 'pets' => 'yes', 'smoking' => 'no']);
        $noPets = $this->home('Douala', 80000, ['property_type' => 'studio', 'pets' => 'no', 'smoking' => 'no']);
        $unknown = $this->home('Douala', 80000, ['property_type' => 'studio']);

        $this->assertSame([(string) $pets->getKey()], $this->ids('?filter[pets]=yes'));
        $this->assertSame([(string) $noPets->getKey()], $this->ids('?filter[pets]=no'));
        $this->assertEqualsCanonicalizing(array_map('strval', [$pets->getKey(), $noPets->getKey()]), $this->ids('?filter[smoking]=no'));
        $this->assertSame([], $this->ids('?filter[pets]=yes&filter[parties]=yes'));
        // « Tous », ou une valeur inconnue, ne filtre rien.
        $this->assertCount(3, $this->ids('?filter[pets]=any'));
        $this->assertCount(3, $this->ids('?filter[pets]=maybe'));
        $this->assertContains((string) $unknown->getKey(), $this->ids());
    }

    public function test_filters_combine_with_each_other_and_the_search(): void
    {
        $match = $this->home('Douala', 80000, ['property_type' => 'studio', 'rental_type' => 'long_term', 'bedrooms' => 1], ['title' => ['fr' => 'Studio Akwa']]);
        $tooExpensive = $this->home('Douala', 200000, ['property_type' => 'studio', 'rental_type' => 'long_term', 'bedrooms' => 1]);
        $otherCity = $this->home('Yaoundé', 80000, ['property_type' => 'studio', 'rental_type' => 'long_term', 'bedrooms' => 1]);
        $house = $this->home('Douala', 80000, ['property_type' => 'house', 'rental_type' => 'long_term', 'bedrooms' => 3, 'bathrooms' => 2, 'available_from' => '2027-01-15']);
        // Une ancienne date sur un meublé ne compte pas (décision 2026-11-20-disponibilite-sans-meuble).
        $furnished = $this->home('Douala', 30000, ['property_type' => 'apartment', 'rental_type' => 'furnished', 'bedrooms' => 2, 'available_from' => '2027-01-15']);

        $this->assertSame([(string) $match->getKey()], $this->ids('?filter[city]=Douala&filter[property_type][]=studio&filter[price][]=&filter[price][]=100000'));
        $this->assertEqualsCanonicalizing(array_map('strval', [$house->getKey(), $match->getKey()]), $this->ids('?filter[property_type][]=studio&filter[property_type][]=house&filter[city]=Douala&filter[price][]=&filter[price][]=100000'));
        $this->assertSame([(string) $furnished->getKey()], $this->ids('?filter[rental_type]=furnished'));
        $this->assertNotContains((string) $furnished->getKey(), $this->ids('?filter[rental_type]=long_term'));
        $this->assertCount(5, $this->ids('?filter[furnished]=any'));
        $this->assertEqualsCanonicalizing(array_map('strval', [$house->getKey(), $furnished->getKey()]), $this->ids('?filter[bedrooms]=2'));
        // Au moins tant de salles de bain ; non renseigné : écarté (décision 2026-11-21-salles-de-bain).
        $this->assertSame([(string) $house->getKey()], $this->ids('?filter[bathrooms]=2'));
        $this->assertSame([(string) $tooExpensive->getKey()], $this->ids('?filter[price][]=150000&filter[price][]='));
        $this->assertSame([(string) $match->getKey()], $this->ids('?filter[price][]=50000&filter[price][]=90000&filter[property_type][]=studio&filter[city]=Douala'));
        // Libre le 1er janvier : pas celui qui se libère le 15.
        $this->assertNotContains((string) $house->getKey(), $this->ids('?filter[available_from]=2027-01-01'));
        $this->assertContains((string) $house->getKey(), $this->ids('?filter[available_from]=2027-02-01'));
        $this->assertContains((string) $furnished->getKey(), $this->ids('?filter[available_from]=2027-01-01'));
        // Avec la recherche.
        $this->assertSame([(string) $match->getKey()], $this->ids('?search=akwa&filter[city]=Douala'));
        $this->assertSame([], $this->ids('?search=akwa&filter[city]=Yaoundé'));
        $this->assertNotContains((string) $otherCity->getKey(), $this->ids('?filter[city]=Douala'));
    }

    public function test_an_invalid_or_unknown_filter_is_ignored(): void
    {
        $this->home('Douala', 1000, ['property_type' => 'studio']);
        $this->home('Yaoundé', 1000, ['property_type' => 'house']);

        $this->assertCount(2, $this->ids('?filter[city]=Paris&filter[price][]=abc&filter[price][]=1&filter[property_type][]=castle&filter[bedrooms]=9&filter[colour]=red&filter=oops'));
        $this->assertCount(2, $this->ids('?filter[available_from]=demain'));
    }

    public function test_a_range_upside_down_is_ignored_and_amenities_must_all_be_there(): void
    {
        $both = $this->home('Douala', 1000, ['property_type' => 'studio', 'amenities' => ['wifi', 'parking']]);
        $wifi = $this->home('Douala', 1000, ['property_type' => 'studio', 'amenities' => ['wifi']]);
        $this->home('Douala', 1000, ['property_type' => 'studio']);

        // Max sous le min : filtre invalide, ignoré.
        $this->assertCount(3, $this->ids('?filter[price][]=5000&filter[price][]=100'));
        $this->assertEqualsCanonicalizing(array_map('strval', [$both->getKey(), $wifi->getKey()]), $this->ids('?filter[amenities][]=wifi'));
        $this->assertSame([(string) $both->getKey()], $this->ids('?filter[amenities][]=wifi&filter[amenities][]=parking'));
    }

    public function test_featured_offers_and_pages_follow_the_filters(): void
    {
        $featuredInYaounde = $this->home('Yaoundé', 1000, ['property_type' => 'studio'], ['featured_until' => now()->addDay()]);
        $featuredInDouala = $this->home('Douala', 1000, ['property_type' => 'studio'], ['featured_until' => now()->addDays(2)]);
        Offer::factory()->count(22)->at(Address::factory()->for($this->cameroon)->state(['city' => 'Douala']))->create(['status' => 'published']);

        $first = $this->withHeaders(['X-Country' => 'CM'])->getJson('/api/v1/lists/offer.browse?filter[city]=Douala')->json('data');
        $second = $this->withHeaders(['X-Country' => 'CM'])->getJson('/api/v1/lists/offer.browse?filter[city]=Douala&cursor='.urlencode($first['next_cursor']))->json('data');
        $ids = [...array_column($first['items'], 'id'), ...array_column($second['items'], 'id')];

        $this->assertSame((string) $featuredInDouala->getKey(), $ids[0]);
        $this->assertNotContains((string) $featuredInYaounde->getKey(), $ids);
        $this->assertCount(23, array_unique($ids));
        $this->assertCount(23, $ids);
    }
}
