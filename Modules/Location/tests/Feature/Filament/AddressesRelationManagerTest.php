<?php

namespace Modules\Location\Tests\Feature\Filament;

use Filament\Actions\Testing\TestAction;
use Livewire\Livewire;
use Modules\Auth\Enums\PermissionEnum;
use Modules\Auth\Tests\Feature\Filament\AuthFilamentTestCase;
use Modules\Location\Filament\Resources\CountryResource\Pages\EditCountry;
use Modules\Location\Filament\Resources\CountryResource\RelationManagers\AddressesRelationManager;
use Modules\Location\Models\Address;
use Modules\Location\Models\Country;

/**
 * Les adresses se gèrent dans la fiche de leur pays, sans doublon (décision
 * 2026-11-18-adresses-liste-generique).
 */
class AddressesRelationManagerTest extends AuthFilamentTestCase
{
    private function manager(Country $country)
    {
        return Livewire::test(AddressesRelationManager::class, [
            'ownerRecord' => $country,
            'pageClass' => EditCountry::class,
        ]);
    }

    private function actingAsAddressManager(): void
    {
        $this->actingAsUserWith(
            PermissionEnum::VIEW_COUNTRIES, PermissionEnum::UPDATE_COUNTRIES,
            PermissionEnum::VIEW_ADDRESSES, PermissionEnum::CREATE_ADDRESSES,
            PermissionEnum::UPDATE_ADDRESSES, PermissionEnum::DELETE_ADDRESSES,
        );
    }

    public function test_the_country_page_lists_only_its_addresses(): void
    {
        $this->actingAsAddressManager();
        $switzerland = Country::factory()->create();
        $mine = Address::factory()->for($switzerland)->create();
        $other = Address::factory()->for(Country::factory())->create();

        $this->manager($switzerland)
            ->assertOk()
            ->assertCanSeeTableRecords([$mine])
            ->assertCanNotSeeTableRecords([$other]);
    }

    public function test_the_addresses_tab_is_hidden_without_view_permission(): void
    {
        $this->actingAsUserWith(PermissionEnum::VIEW_COUNTRIES, PermissionEnum::UPDATE_COUNTRIES);

        $this->assertFalse(AddressesRelationManager::canViewForRecord(Country::factory()->create(), EditCountry::class));
    }

    public function test_creates_an_address_in_the_country(): void
    {
        $this->actingAsAddressManager();
        $switzerland = Country::factory()->create();

        $this->manager($switzerland)
            // La région du pays, à la place du pays (décision 2026-11-25-adresse-sans-pays).
            ->callAction(TestAction::make('create')->table(), [
                'street' => 'Rue du Lac 4', 'zip' => '1200', 'city' => 'Genève', 'region_id' => (string) $this->regionOf($switzerland), 'latitude' => '46.2', 'longitude' => '6.1',
            ])
            ->assertHasNoFormErrors();

        $address = Address::query()->sole();
        $this->assertTrue($address->country->is($switzerland));
        $this->assertSame('46.2000000', $address->latitude);
    }

    public function test_refuses_a_duplicate_on_creation(): void
    {
        $this->actingAsAddressManager();
        $switzerland = Country::factory()->create();
        $existing = Address::factory()->for($switzerland)->create(['street' => 'Rue du Lac 4', 'zip' => '1200', 'city' => 'Genève']);

        $this->manager($switzerland)
            ->callAction(TestAction::make('create')->table(), ['street' => 'rue du lac  4', 'zip' => '1200', 'city' => 'GENÈVE', 'region_id' => (string) $existing->region_id])
            ->assertHasFormErrors(['street']);

        $this->assertSame(1, Address::query()->count());
    }

    public function test_refuses_an_edit_into_a_duplicate_but_accepts_its_own_values(): void
    {
        $this->actingAsAddressManager();
        $switzerland = Country::factory()->create();
        Address::factory()->for($switzerland)->create(['street' => 'Rue du Lac 4', 'zip' => '1200', 'city' => 'Genève']);
        $address = Address::factory()->for($switzerland)->create(['street' => 'Rue du Rhône 10', 'zip' => '1204', 'city' => 'Genève']);

        $this->manager($switzerland)
            ->callAction(TestAction::make('edit')->table($address), ['street' => 'Rue du Lac 4', 'zip' => '1200'])
            ->assertHasFormErrors(['street']);

        $this->manager($switzerland)
            ->callAction(TestAction::make('edit')->table($address), ['latitude' => '46.2'])
            ->assertHasNoFormErrors();

        $this->assertSame('46.2000000', $address->fresh()->latitude);
    }

    public function test_rejects_out_of_range_coordinates(): void
    {
        $this->actingAsAddressManager();

        $this->manager(Country::factory()->create())
            ->callAction(TestAction::make('create')->table(), [
                'street' => 'Rue du Lac 4', 'zip' => '1200', 'city' => 'Genève', 'latitude' => '91', 'longitude' => '-181',
            ])
            ->assertHasFormErrors(['latitude', 'longitude']);
    }

    public function test_create_is_hidden_without_create_permission(): void
    {
        $this->actingAsUserWith(PermissionEnum::VIEW_COUNTRIES, PermissionEnum::UPDATE_COUNTRIES, PermissionEnum::VIEW_ADDRESSES);

        $this->manager(Country::factory()->create())->assertActionHidden(TestAction::make('create')->table());
    }
}
