<?php

namespace Modules\Location\Tests\Feature;

use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Location\Models\Address;
use Modules\Location\Models\Country;
use Modules\Location\Services\AddressService;
use Tests\TestCase;

/**
 * Une adresse n'existe qu'une fois par pays : retrouvée, créée ou suggérée
 * (décisions 2026-11-18-adresses-liste-generique et 2026-11-18-useraddress-dans-auth).
 */
class AddressServiceTest extends TestCase
{
    use RefreshDatabase;

    private function service(): AddressService
    {
        return app(AddressService::class);
    }

    /**
     * @return array<string, mixed>
     */
    private function input(Country $country, array $overrides = []): array
    {
        return [
            'region_id' => $this->regionOf($country),
            'street' => 'Rue du Lac 4',
            'zip' => '1200',
            'city' => 'Genève',
            'latitude' => '46.2043907',
            'longitude' => '6.1431577',
            ...$overrides,
        ];
    }

    public function test_a_new_address_is_created_with_its_coordinates(): void
    {
        $address = $this->service()->findOrCreate($this->input(Country::factory()->create()));

        $this->assertSame(1, Address::query()->count());
        $this->assertSame('Rue du Lac 4', $address->street);
        $this->assertSame('46.2043907', $address->latitude);
    }

    public function test_an_existing_address_is_found_and_keeps_its_coordinates(): void
    {
        $country = Country::factory()->create();
        $existing = $this->service()->findOrCreate($this->input($country));

        // Casse et espaces différents : c'est la même adresse.
        $found = $this->service()->findOrCreate($this->input($country, [
            'street' => '  rue du LAC   4 ', 'city' => 'GENÈVE', 'latitude' => '10', 'longitude' => '10',
        ]));

        $this->assertTrue($found->is($existing));
        $this->assertSame(1, Address::query()->count());
        $this->assertSame('46.2043907', $existing->fresh()->latitude);
        $this->assertNull($this->service()->find($this->input($country, ['zip' => '9999'])));
    }

    public function test_the_same_street_in_another_country_is_another_address(): void
    {
        $this->service()->findOrCreate($this->input(Country::factory()->create()));
        $this->service()->findOrCreate($this->input(Country::factory()->create()));

        $this->assertSame(2, Address::query()->count());
    }

    public function test_suggestions_match_part_of_the_street_in_the_country(): void
    {
        $switzerland = Country::factory()->create();
        Address::factory()->for($switzerland)->create(['street' => 'Rue du Lac 4', 'zip' => '1200', 'city' => 'Genève']);
        Address::factory()->for($switzerland)->create(['street' => 'Quai du Lac 1', 'zip' => '1201', 'city' => 'Genève']);
        Address::factory()->for($switzerland)->create(['street' => 'Rue du Rhône 10']);
        Address::factory()->for(Country::factory())->create(['street' => 'Rue du Lac 4']);

        $labels = $this->service()->suggest('DU LAC', $switzerland->getKey(), 5)->map->suggestionLabel()->all();

        $this->assertSame(['Quai du Lac 1 — 1201 Genève', 'Rue du Lac 4 — 1200 Genève'], $labels);
        $this->assertCount(2, $this->service()->suggest('rue du lac', null, 5));
        $this->assertCount(1, $this->service()->suggest('du lac', $switzerland->getKey(), 1));
        $this->assertSame('Rue du Lac 4', $this->service()->fromSuggestionLabel('Rue du Lac 4 — 1200 Genève', $switzerland->getKey())?->street);
    }

    public function test_a_like_wildcard_is_searched_literally(): void
    {
        Address::factory()->create(['street' => 'Rue du Lac 4']);
        Address::factory()->create(['street' => 'Rue 100% Bio']);
        Address::factory()->create(['street' => 'Chemin_des_Vignes']);

        $this->assertCount(0, $this->service()->suggest('%%%', null, 5));
        $this->assertCount(0, $this->service()->suggest('du_lac', null, 5));
        // Même règle que les listes de l'app (ContainsSearch) : les jokers sont des caractères.
        $this->assertSame(['Rue 100% Bio'], $this->service()->suggest('100%', null, 5)->pluck('street')->all());
        $this->assertSame(['Chemin_des_Vignes'], $this->service()->suggest('n_d', null, 5)->pluck('street')->all());
    }

    public function test_the_database_refuses_a_duplicate(): void
    {
        $country = Country::factory()->create();
        Address::factory()->for($country)->create(['street' => 'Rue du Lac 4', 'zip' => '1200', 'city' => 'Genève']);

        $this->expectException(UniqueConstraintViolationException::class);

        Address::factory()->for($country)->create(['street' => 'RUE DU LAC 4', 'zip' => '1200', 'city' => 'genève']);
    }

    public function test_deleting_the_country_removes_its_addresses(): void
    {
        $address = $this->service()->findOrCreate($this->input(Country::factory()->create()));

        // Son pays est celui de sa région (décision 2026-11-25-adresse-sans-pays).
        $address->country->delete();

        $this->assertNull($address->fresh());
    }
}
