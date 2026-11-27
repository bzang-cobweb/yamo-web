<?php

namespace Modules\Auth\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Auth\Models\User;
use Modules\Auth\Models\UserAddress;
use Modules\Auth\Services\UserAddressService;
use Modules\Location\Models\Address;
use Modules\Location\Models\Country;
use Tests\TestCase;

/**
 * Un compte vise une adresse partagée par une `UserAddress`, avec son
 * destinataire (décisions 2026-11-18-adresse-du-compte-modele et
 * 2026-11-18-useraddress-dans-auth).
 */
class UserAddressServiceTest extends TestCase
{
    use RefreshDatabase;

    private function service(): UserAddressService
    {
        return app(UserAddressService::class);
    }

    /**
     * @return array<string, mixed>
     */
    private function input(Country $country, array $overrides = []): array
    {
        return [
            'gender' => 'female',
            'first_name' => 'Jeanne',
            'last_name' => 'Dupont',
            'region_id' => $this->regionOf($country),
            'street' => 'Rue du Lac 4',
            'zip' => '1200',
            'city' => 'Genève',
            'latitude' => '46.2043907',
            'longitude' => '6.1431577',
            ...$overrides,
        ];
    }

    public function test_adding_a_new_address_creates_it_then_the_user_address(): void
    {
        $user = User::factory()->create();

        $entry = $this->service()->attach($user, $this->input(Country::factory()->create()));

        $this->assertSame(1, Address::query()->count());
        $this->assertSame('Rue du Lac 4', $entry->address->street);
        $this->assertSame('46.2043907', $entry->address->latitude);
        // Le genre envoyé n'est plus écrit (demande du 2026-11-27).
        $this->assertNull($entry->gender);
        $this->assertSame('Jeanne Dupont', $entry->recipient());
        $this->assertTrue($user->addresses()->sole()->is($entry));
    }

    public function test_adding_an_existing_address_reuses_it(): void
    {
        $country = Country::factory()->create();
        $existing = $this->service()->attach(User::factory()->create(), $this->input($country))->address;

        // Casse et espaces différents : c'est la même adresse.
        $entry = $this->service()->attach(User::factory()->create(), $this->input($country, [
            'street' => '  rue du LAC   4 ', 'city' => 'GENÈVE', 'latitude' => '10', 'longitude' => '10',
        ]));

        $this->assertTrue($entry->address->is($existing));
        $this->assertSame(1, Address::query()->count());
        $this->assertSame(2, UserAddress::query()->where('address_id', $existing->getKey())->count());
        // L'adresse existante garde ses coordonnées.
        $this->assertSame('46.2043907', $existing->fresh()->latitude);
    }

    public function test_the_same_address_twice_with_two_recipients(): void
    {
        $user = User::factory()->create();
        $country = Country::factory()->create();

        $this->service()->attach($user, $this->input($country));
        $this->service()->attach($user, $this->input($country, ['first_name' => 'Paul']));

        $this->assertSame(1, Address::query()->count());
        $this->assertSame(['Jeanne', 'Paul'], $user->addresses()->pluck('first_name')->all());
    }

    public function test_the_same_street_in_another_country_is_another_address(): void
    {
        $user = User::factory()->create();

        $this->service()->attach($user, $this->input(Country::factory()->create()));
        $this->service()->attach($user, $this->input(Country::factory()->create()));

        $this->assertSame(2, Address::query()->count());
    }

    public function test_changing_only_the_recipient_keeps_the_address(): void
    {
        $country = Country::factory()->create();
        $entry = $this->service()->attach(User::factory()->create(), $this->input($country));
        $address = $entry->address;

        $updated = $this->service()->update($entry, $this->input($country, ['first_name' => 'Jeanne-Marie', 'gender' => 'male']));

        $this->assertTrue($updated->address->is($address));
        $this->assertSame('Jeanne-Marie', $updated->first_name);
        $this->assertNull($updated->gender);
        $this->assertSame(1, Address::query()->count());
    }

    public function test_changing_the_address_leaves_the_shared_one_intact(): void
    {
        $country = Country::factory()->create();
        $entry = $this->service()->attach(User::factory()->create(), $this->input($country));
        $partner = $this->service()->attach(User::factory()->create(), $this->input($country));
        $shared = $entry->address;

        $updated = $this->service()->update($entry, $this->input($country, ['street' => 'Rue du Rhône 10']));

        $this->assertSame('Rue du Rhône 10', $updated->address->street);
        $this->assertSame('Rue du Lac 4', $shared->fresh()->street);
        $this->assertTrue($partner->fresh()->address->is($shared));
    }

    public function test_changing_to_an_existing_address_reuses_it(): void
    {
        $country = Country::factory()->create();
        $existing = Address::factory()->for($country)->create(['street' => 'Rue du Rhône 10', 'zip' => '1204', 'city' => 'Genève']);
        $entry = $this->service()->attach(User::factory()->create(), $this->input($country));

        $updated = $this->service()->update($entry, $this->input($country, ['street' => 'rue du rhône 10', 'zip' => '1204']));

        $this->assertTrue($updated->address->is($existing));
        $this->assertSame(2, Address::query()->count());
    }

    public function test_removing_keeps_the_address_in_its_country(): void
    {
        $entry = $this->service()->attach(User::factory()->create(), $this->input(Country::factory()->create()));

        $this->service()->detach($entry);

        $this->assertSame(0, UserAddress::query()->count());
        $this->assertNotNull($entry->address->fresh());
    }

    public function test_purging_the_account_or_deleting_the_country_removes_its_entries(): void
    {
        $user = User::factory()->create();
        $entry = $this->service()->attach($user, $this->input(Country::factory()->create()));
        $address = $entry->address;

        $user->forceDelete();
        $this->assertNull($entry->fresh());

        $other = $this->service()->attach(User::factory()->create(), $this->input($address->country));
        $address->country->delete();
        $this->assertNull($other->fresh());
    }
}
