<?php

namespace Modules\Offer\Tests\Feature\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Auth\Models\User;
use Modules\Location\Models\Address;
use Modules\Location\Models\Country;
use Modules\Location\Models\Region;
use Modules\Offer\Database\Seeders\OfferDatabaseSeeder;
use Modules\Offer\Models\Offer;
use Modules\Offer\Models\OfferCategory;
use Tests\TestCase;

/**
 * Filtres de la vitrine (décision 2026-11-19-contrat-filtres-vitrine).
 */
class OfferFiltersApiTest extends TestCase
{
    use RefreshDatabase;

    private Country $cameroon;

    /** L'id d'une case de « Règles » ou « Caractéristiques », par son nom français. */
    private function flag(string $name): int
    {
        return (int) OfferCategory::query()->whereNotNull('parent_id')->where('name->fr', $name)->value('id');
    }

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
        // Une seule région : la recherche en exige une (décision 2026-11-25-region-obligatoire-recherche) ; ces tests portent
        // sur les autres filtres.
        return Offer::factory()->at(Address::factory()->create(['city' => $city, 'region_id' => $this->regionOf($this->cameroon)]))->create([
            'status' => 'published', 'price' => (string) $price,
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

    public function test_the_region_filter_groups_the_cities_of_a_region(): void
    {
        // Décision 2026-11-25-region-de-l-adresse : la région avant la ville, ses offres de toutes ses villes. Yaoundé
        // d'abord, dans la première région du pays (`home()`).
        $this->home('Yaoundé', 1000, []);
        $littoral = Region::factory()->create(['country_id' => $this->cameroon->getKey(), 'name' => ['fr' => 'Littoral', 'en' => 'Littoral']]);
        $douala = Offer::factory()->at(Address::factory()->for($this->cameroon)->create(['city' => 'Douala', 'region_id' => $littoral->getKey()]))->create(['status' => 'published']);
        $edea = Offer::factory()->at(Address::factory()->for($this->cameroon)->create(['city' => 'Edéa', 'region_id' => $littoral->getKey()]))->create(['status' => 'published']);

        $filters = array_column($this->withHeaders(['X-Country' => 'CM', 'Accept-Language' => 'fr'])->getJson('/api/v1/lists/offer.browse')->json('data.filters'), null, 'name');
        $this->assertSame('Région', $filters['region']['label']);
        // Sans l'option « Tous » (décision 2026-11-25-region-sans-tous) ; la ville la garde.
        $this->assertFalse($filters['region']['all_option']);
        $this->assertArrayNotHasKey('all_option', $filters['city']);
        $this->assertContains('Littoral', array_column($filters['region']['options'], 'label'));

        $this->assertEqualsCanonicalizing([(string) $douala->getKey(), (string) $edea->getKey()], $this->ids('?filter[region]='.$littoral->getKey()));
    }

    public function test_the_search_always_has_a_region_the_first_with_offers_by_default(): void
    {
        // Décision 2026-11-25-region-obligatoire-recherche : la première région qui a des offres, dans l'ordre du
        // back-office ; une région sans offre n'est pas proposée.
        $empty = Region::factory()->create(['country_id' => $this->cameroon->getKey(), 'position' => 1]);
        $centre = Region::factory()->create(['country_id' => $this->cameroon->getKey(), 'name' => ['fr' => 'Centre', 'en' => 'Centre'], 'position' => 2]);
        $littoral = Region::factory()->create(['country_id' => $this->cameroon->getKey(), 'name' => ['fr' => 'Littoral', 'en' => 'Littoral'], 'position' => 3]);
        $yaounde = Offer::factory()->at(Address::factory()->for($this->cameroon)->create(['city' => 'Yaoundé', 'region_id' => $centre->getKey()]))->create(['status' => 'published']);
        $douala = Offer::factory()->at(Address::factory()->for($this->cameroon)->create(['city' => 'Douala', 'region_id' => $littoral->getKey()]))->create(['status' => 'published']);

        $definition = $this->withHeaders(['X-Country' => 'CM', 'Accept-Language' => 'fr'])->getJson('/api/v1/lists/offer.browse')->assertOk()->json('data');
        $region = collect($definition['filters'])->firstWhere('name', 'region');
        $this->assertTrue($region['required']);
        $this->assertSame((string) $centre->getKey(), $region['default']);
        $this->assertNotContains((string) $empty->getKey(), array_column($region['options'], 'value'));

        // Sans région envoyée : celle par défaut ; avec, la sienne.
        $this->assertSame([(string) $yaounde->getKey()], $this->ids());
        $this->assertSame('1 logement', $definition['heading']['title']);
        $this->assertSame([(string) $douala->getKey()], $this->ids('?filter[region]='.$littoral->getKey()));
    }

    public function test_the_city_filter_offers_the_cities_of_the_chosen_region(): void
    {
        // Décision 2026-11-25-ville-selon-region : chaque ville a sa région pour parent ; une ville d'une autre région
        // que celle choisie est ignorée.
        $centre = Region::factory()->create(['country_id' => $this->cameroon->getKey(), 'name' => ['fr' => 'Centre', 'en' => 'Centre'], 'position' => 1]);
        $littoral = Region::factory()->create(['country_id' => $this->cameroon->getKey(), 'name' => ['fr' => 'Littoral', 'en' => 'Littoral'], 'position' => 2]);
        Offer::factory()->at(Address::factory()->create(['city' => 'Yaoundé', 'region_id' => $centre->getKey()]))->create(['status' => 'published']);
        $douala = Offer::factory()->at(Address::factory()->create(['city' => 'Douala', 'region_id' => $littoral->getKey()]))->create(['status' => 'published']);
        Offer::factory()->at(Address::factory()->create(['city' => 'Edéa', 'region_id' => $littoral->getKey()]))->create(['status' => 'published']);

        $city = collect($this->withHeaders(['X-Country' => 'CM', 'Accept-Language' => 'fr'])->getJson('/api/v1/lists/offer.browse')->json('data.filters'))->firstWhere('name', 'city');
        $this->assertSame('region', $city['depends_on']);
        $this->assertSame((string) $littoral->getKey(), collect($city['options'])->firstWhere('value', 'Douala')['parent']);
        $this->assertTrue($city['country_scoped']);

        $this->assertSame([(string) $douala->getKey()], $this->ids('?filter[region]='.$littoral->getKey().'&filter[city]=Douala'));
        // Douala n'est pas dans la région par défaut (Centre) : la ville est ignorée.
        $this->assertCount(1, $this->ids('?filter[city]=Douala'));
        $this->assertNotContains((string) $douala->getKey(), $this->ids('?filter[city]=Douala'));
    }

    public function test_my_homes_keep_an_optional_region(): void
    {
        $user = User::factory()->create();

        $region = collect($this->withToken($user->createToken('iPhone')->plainTextToken)->withHeaders(['X-Country' => 'CM', 'Accept-Language' => 'fr'])
            ->getJson('/api/v1/lists/offer.mine')->json('data.filters'))->firstWhere('name', 'region');
        $this->assertFalse($region['required']);
        $this->assertNull($region['default']);
    }

    public function test_the_list_describes_its_filters(): void
    {
        $this->home('Yaoundé', 1000, ['property_type' => 'studio']);
        $this->home('Douala', 1000, ['property_type' => 'house']);
        Offer::factory()->at(Address::factory()->for($this->cameroon)->create(['city' => 'Kribi']))->create(['status' => 'draft']);

        $filters = array_column($this->withHeaders(['X-Country' => 'CM', 'Accept-Language' => 'fr'])->getJson('/api/v1/lists/offer.browse')->json('data.filters'), null, 'name');

        // La région avant la ville (décision 2026-11-25-region-de-l-adresse).
        // Les équipements, puis les règles, en puces (décision 2026-11-26-filtres-en-puces).
        $this->assertSame(['region', 'city', 'price', 'property_type', 'rental_type', 'bedrooms', 'bathrooms', 'available_from', 'feature_flags', 'rule_flags', 'identified'], array_keys($filters));
        // « Prix », quel que soit le domaine (décision 2026-11-26-filtre-prix-membre-identifie).
        $this->assertSame('Prix', $filters['price']['label']);
        // « Libre dès le » : sans type de location choisi ou pour un non meublé, pas pour la location meublée
        // (décision 2026-11-22-filtre-libre-des-sans-meuble).
        $this->assertSame(['field' => 'rental_type', 'value' => '', 'values' => ['', 'long_term', 'shared']], $filters['available_from']['visible_when']);
        // Les villes des offres publiées seulement, dans l'ordre.
        $this->assertSame(['Douala', 'Yaoundé'], array_column($filters['city']['options'], 'value'));
        // Un seul intervalle pour le loyer ; le type et les équipements en puces.
        $this->assertSame('range', $filters['price']['type']);
        $this->assertSame('FCFA', $filters['price']['suffix']);
        $this->assertSame('chips', $filters['property_type']['type']);
        $this->assertSame('chips', $filters['feature_flags']['type']);
        $this->assertSame('Équipements', $filters['feature_flags']['label']);
        $this->assertCount(15, $filters['feature_flags']['options']);
        // Le type de location en puces, plusieurs à la fois (décision 2026-11-26-filtres-en-puces).
        $this->assertSame('chips', $filters['rental_type']['type']);
        $this->assertSame(['long_term', 'furnished', 'shared'], array_column($filters['rental_type']['options'], 'value'));
    }

    public function test_rules_filter_keeps_offers_with_every_chosen_rule(): void
    {
        // Les règles en puces, chaque règle choisie cochée sur l'offre (décision 2026-11-26-filtres-en-puces).
        $filters = array_column($this->withHeaders(['X-Country' => 'CM', 'Accept-Language' => 'fr'])->getJson('/api/v1/lists/offer.browse')->json('data.filters'), null, 'name');
        $this->assertSame('chips', $filters['rule_flags']['type']);
        $this->assertSame('Règles', $filters['rule_flags']['label']);
        $this->assertSame(['Animaux acceptés', 'Fumeurs acceptés', 'Fêtes et événements autorisés'], array_column($filters['rule_flags']['options'], 'label'));

        $pets = (string) $this->flag('Animaux acceptés');
        $parties = (string) $this->flag('Fêtes et événements autorisés');
        $withPets = $this->home('Douala', 80000, ['property_type' => 'studio', 'rule_flags' => [$pets]]);
        $both = $this->home('Douala', 80000, ['property_type' => 'studio', 'rule_flags' => [$pets, $parties]]);
        $unknown = $this->home('Douala', 80000, ['property_type' => 'studio']);

        $this->assertEqualsCanonicalizing([(string) $withPets->getKey(), (string) $both->getKey()], $this->ids("?filter[rule_flags][]={$pets}"));
        $this->assertSame([(string) $both->getKey()], $this->ids("?filter[rule_flags][]={$pets}&filter[rule_flags][]={$parties}"));
        // Aucune règle choisie : rien n'est filtré.
        $this->assertContains((string) $unknown->getKey(), $this->ids());
    }

    public function test_identified_filter_keeps_offers_by_their_publisher(): void
    {
        // Décision 2026-11-26-filtre-prix-membre-identifie.
        $filters = array_column($this->withHeaders(['X-Country' => 'CM', 'Accept-Language' => 'fr'])->getJson('/api/v1/lists/offer.browse')->json('data.filters'), null, 'name');
        $this->assertSame('segmented', $filters['identified']['type']);
        $this->assertSame('Membre identifié', $filters['identified']['label']);
        $this->assertSame(['Tous', 'Oui', 'Non'], array_column($filters['identified']['options'], 'label'));
        $this->assertSame('any', $filters['identified']['default']);

        $identified = $this->home('Douala', 80000, ['property_type' => 'studio'], ['user_id' => User::factory()->create(['identified_at' => now()])->getKey()]);
        $unidentified = $this->home('Douala', 80000, ['property_type' => 'studio'], ['user_id' => User::factory()->create(['identified_at' => null])->getKey()]);

        $this->assertSame([(string) $identified->getKey()], $this->ids('?filter[identified]=yes'));
        $this->assertSame([(string) $unidentified->getKey()], $this->ids('?filter[identified]=no'));
        $this->assertCount(2, $this->ids('?filter[identified]=any'));
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
        $this->assertSame([(string) $furnished->getKey()], $this->ids('?filter[rental_type][]=furnished'));
        $this->assertNotContains((string) $furnished->getKey(), $this->ids('?filter[rental_type][]=long_term'));
        // Plusieurs types de location à la fois (décision 2026-11-26-filtres-en-puces).
        $this->assertCount(5, $this->ids('?filter[rental_type][]=long_term&filter[rental_type][]=furnished'));
        $this->assertCount(5, $this->ids('?filter[furnished]=any'));
        $this->assertEqualsCanonicalizing(array_map('strval', [$house->getKey(), $furnished->getKey()]), $this->ids('?filter[bedrooms][]=2&filter[bedrooms][]=3'));
        // Les nombres choisis, exacts ; « 4+ » (la dernière puce) : 4 ou plus (décision 2026-11-26-filtres-en-puces).
        $this->assertSame([(string) $furnished->getKey()], $this->ids('?filter[bedrooms][]=2'));
        $this->assertSame([], $this->ids('?filter[bedrooms][]=4'));
        $this->assertSame([(string) $house->getKey()], $this->ids('?filter[bathrooms][]=2&filter[bathrooms][]=3&filter[bedrooms][]=3'));
        // Les salles de bain choisies ; non renseigné : écarté (décision 2026-11-26-filtres-en-puces).
        $this->assertSame([(string) $house->getKey()], $this->ids('?filter[bathrooms][]=2'));
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
        // Par id de case « Équipements » (décision 2026-11-25-equipements-en-categories).
        [$wifiId, $parkingId] = [(string) $this->flag('Wifi'), (string) $this->flag('Parking')];
        $both = $this->home('Douala', 1000, ['property_type' => 'studio', 'feature_flags' => [$wifiId, $parkingId]]);
        $wifi = $this->home('Douala', 1000, ['property_type' => 'studio', 'feature_flags' => [$wifiId]]);
        $this->home('Douala', 1000, ['property_type' => 'studio']);

        // Max sous le min : filtre invalide, ignoré.
        $this->assertCount(3, $this->ids('?filter[price][]=5000&filter[price][]=100'));
        $this->assertEqualsCanonicalizing(array_map('strval', [$both->getKey(), $wifi->getKey()]), $this->ids("?filter[feature_flags][]={$wifiId}"));
        $this->assertSame([(string) $both->getKey()], $this->ids("?filter[feature_flags][]={$wifiId}&filter[feature_flags][]={$parkingId}"));
    }

    public function test_featured_offers_and_pages_follow_the_filters(): void
    {
        $featuredInYaounde = $this->home('Yaoundé', 1000, ['property_type' => 'studio'], ['featured_until' => now()->addDay()]);
        $featuredInDouala = $this->home('Douala', 1000, ['property_type' => 'studio'], ['featured_until' => now()->addDays(2)]);
        Offer::factory()->count(22)->at(Address::factory()->state(['city' => 'Douala', 'region_id' => $this->regionOf($this->cameroon)]))->create(['status' => 'published']);

        $first = $this->withHeaders(['X-Country' => 'CM'])->getJson('/api/v1/lists/offer.browse?filter[city]=Douala')->json('data');
        $second = $this->withHeaders(['X-Country' => 'CM'])->getJson('/api/v1/lists/offer.browse?filter[city]=Douala&cursor='.urlencode($first['next_cursor']))->json('data');
        $ids = [...array_column($first['items'], 'id'), ...array_column($second['items'], 'id')];

        $this->assertSame((string) $featuredInDouala->getKey(), $ids[0]);
        $this->assertNotContains((string) $featuredInYaounde->getKey(), $ids);
        $this->assertCount(23, array_unique($ids));
        $this->assertCount(23, $ids);
    }
}
