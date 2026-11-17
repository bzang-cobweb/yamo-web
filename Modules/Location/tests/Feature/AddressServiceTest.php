<?php

namespace Modules\Location\Tests\Feature;

use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Auth\Models\User;
use Modules\Location\Models\Address;
use Modules\Location\Models\Country;
use Modules\Location\Services\AddressService;
use Tests\TestCase;

/**
 * Une adresse n'existe qu'une fois par pays ; un compte y est rattaché par le
 * pivot (décision 2026-11-18-adresses-liste-generique).
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
            'country_id' => $country->getKey(),
            'street' => 'Rue du Lac 4',
            'zip' => '1200',
            'city' => 'Genève',
            'latitude' => '46.2043907',
            'longitude' => '6.1431577',
            ...$overrides,
        ];
    }

    public function test_attaching_a_new_address_creates_it_then_the_pivot(): void
    {
        $user = User::factory()->create();
        $country = Country::factory()->create();

        $address = $this->service()->attach($user, $this->input($country));

        $this->assertSame(1, Address::query()->count());
        $this->assertSame('Rue du Lac 4', $address->street);
        $this->assertSame('46.2043907', $address->latitude);
        $this->assertTrue($user->addresses()->sole()->is($address));
    }

    public function test_attaching_an_existing_address_only_creates_the_pivot(): void
    {
        $country = Country::factory()->create();
        $first = User::factory()->create();
        $second = User::factory()->create();
        $existing = $this->service()->attach($first, $this->input($country));

        // Casse et espaces différents : c'est la même adresse.
        $address = $this->service()->attach($second, $this->input($country, [
            'street' => '  rue du LAC   4 ', 'city' => 'GENÈVE', 'latitude' => '10', 'longitude' => '10',
        ]));

        $this->assertTrue($address->is($existing));
        $this->assertSame(1, Address::query()->count());
        $this->assertSame(2, $existing->users()->count());
        // L'adresse existante garde ses coordonnées.
        $this->assertSame('46.2043907', $address->fresh()->latitude);
    }

    public function test_the_same_street_in_another_country_is_another_address(): void
    {
        $user = User::factory()->create();

        $this->service()->attach($user, $this->input(Country::factory()->create()));
        $this->service()->attach($user, $this->input(Country::factory()->create()));

        $this->assertSame(2, Address::query()->count());
        $this->assertSame(2, $user->addresses()->count());
    }

    public function test_attaching_twice_keeps_a_single_pivot(): void
    {
        $user = User::factory()->create();
        $country = Country::factory()->create();

        $this->service()->attach($user, $this->input($country));
        $this->service()->attach($user, $this->input($country));

        $this->assertSame(1, $user->addresses()->count());
    }

    public function test_replacing_moves_the_pivot_without_touching_the_shared_address(): void
    {
        $country = Country::factory()->create();
        $user = User::factory()->create();
        $partner = User::factory()->create();
        $old = $this->service()->attach($user, $this->input($country));
        $this->service()->attach($partner, $this->input($country));

        $new = $this->service()->replace($user, $old, $this->input($country, ['street' => 'Rue du Rhône 10']));

        $this->assertFalse($new->is($old));
        $this->assertSame('Rue du Lac 4', $old->fresh()->street);
        $this->assertTrue($user->addresses()->sole()->is($new));
        $this->assertTrue($partner->addresses()->sole()->is($old));
    }

    public function test_replacing_with_an_existing_address_reuses_it(): void
    {
        $country = Country::factory()->create();
        $user = User::factory()->create();
        $existing = Address::factory()->for($country)->create(['street' => 'Rue du Rhône 10', 'zip' => '1204', 'city' => 'Genève']);
        $old = $this->service()->attach($user, $this->input($country));

        $new = $this->service()->replace($user, $old, $this->input($country, ['street' => 'rue du rhône 10', 'zip' => '1204']));

        $this->assertTrue($new->is($existing));
        $this->assertSame(2, Address::query()->count());
        $this->assertTrue($user->addresses()->sole()->is($existing));
    }

    public function test_detaching_keeps_the_address_in_its_country(): void
    {
        $user = User::factory()->create();
        $address = $this->service()->attach($user, $this->input(Country::factory()->create()));

        $this->service()->detach($user, $address);

        $this->assertSame(0, $user->addresses()->count());
        $this->assertTrue($address->exists && $address->fresh() !== null);
    }

    public function test_the_database_refuses_a_duplicate(): void
    {
        $country = Country::factory()->create();
        Address::factory()->for($country)->create(['street' => 'Rue du Lac 4', 'zip' => '1200', 'city' => 'Genève']);

        $this->expectException(UniqueConstraintViolationException::class);

        Address::factory()->for($country)->create(['street' => 'RUE DU LAC 4', 'zip' => '1200', 'city' => 'genève']);
    }

    public function test_deleting_the_country_or_purging_the_account_removes_the_rows(): void
    {
        $user = User::factory()->create();
        $address = $this->service()->attach($user, $this->input(Country::factory()->create()));

        $user->forceDelete();
        $this->assertSame(0, $address->users()->count());

        $address->country->delete();
        $this->assertNull($address->fresh());
    }
}
