<?php

namespace Modules\Auth\Tests\Feature\Filament;

use Filament\Actions\Testing\TestAction;
use Livewire\Livewire;
use Modules\Auth\Enums\GenderEnum;
use Modules\Auth\Enums\PermissionEnum;
use Modules\Auth\Filament\Resources\UserResource\Pages\EditUser;
use Modules\Auth\Filament\Resources\UserResource\RelationManagers\AddressesRelationManager;
use Modules\Auth\Models\User;
use Modules\Location\Models\Address;
use Modules\Location\Models\Country;
use Modules\Location\Models\UserAddress;

/**
 * Les adresses d'un utilisateur (`UserAddress`) : ajouter ou modifier reprend
 * l'adresse identique au lieu de la dupliquer, retirer ne supprime que l'entrée,
 * et la rue propose les adresses existantes (décision
 * 2026-11-18-adresse-du-compte-modele).
 */
class UserAddressesRelationManagerTest extends AuthFilamentTestCase
{
    private function manager(User $user)
    {
        return Livewire::test(AddressesRelationManager::class, [
            'ownerRecord' => $user,
            'pageClass' => EditUser::class,
        ]);
    }

    private function actingAsAddressManager(): void
    {
        $this->actingAsUserWith(
            PermissionEnum::VIEW_USERS, PermissionEnum::UPDATE_USERS,
            PermissionEnum::VIEW_ADDRESSES, PermissionEnum::CREATE_ADDRESSES, PermissionEnum::UPDATE_ADDRESSES,
        );
    }

    /**
     * @return array<string, string>
     */
    private function input(Country $country, array $overrides = []): array
    {
        return [
            'gender' => 'female', 'first_name' => 'Jeanne', 'last_name' => 'Dupont',
            'country_id' => (string) $country->getKey(), 'street' => 'Rue du Lac 4', 'zip' => '1200', 'city' => 'Genève',
            ...$overrides,
        ];
    }

    public function test_lists_only_the_user_addresses_with_their_recipient(): void
    {
        $this->actingAsAddressManager();
        $user = User::factory()->create();
        $mine = UserAddress::factory()->for($user)->create(['first_name' => 'Jeanne', 'last_name' => 'Dupont']);
        $other = UserAddress::factory()->create();

        $this->manager($user)
            ->assertOk()
            ->assertCanSeeTableRecords([$mine])
            ->assertCanNotSeeTableRecords([$other])
            ->assertSee('Jeanne Dupont');
    }

    public function test_the_recipient_starts_as_the_user(): void
    {
        $this->actingAsAddressManager();
        $user = User::factory()->create(['gender' => 'male', 'first_name' => 'Paul', 'last_name' => 'Martin']);

        $this->manager($user)
            ->mountAction(TestAction::make('create')->table())
            ->assertSchemaStateSet(['gender' => 'male', 'first_name' => 'Paul', 'last_name' => 'Martin']);
    }

    public function test_adding_a_new_address_creates_it_and_the_entry(): void
    {
        $this->actingAsAddressManager();
        $user = User::factory()->create();
        $country = Country::factory()->create();

        $this->manager($user)
            ->callAction(TestAction::make('create')->table(), $this->input($country))
            ->assertHasNoFormErrors();

        $entry = $user->addresses()->sole();
        $this->assertSame('Jeanne Dupont', $entry->recipient());
        $this->assertSame(GenderEnum::FEMALE, $entry->gender);
        $this->assertTrue($entry->address->country->is($country));
    }

    public function test_adding_an_existing_address_reuses_it(): void
    {
        $this->actingAsAddressManager();
        $user = User::factory()->create();
        $existing = Address::factory()->create(['street' => 'Rue du Lac 4', 'zip' => '1200', 'city' => 'Genève']);

        $this->manager($user)
            ->callAction(TestAction::make('create')->table(), $this->input($existing->country, ['street' => 'RUE DU LAC 4', 'city' => 'genève']))
            ->assertHasNoFormErrors();

        $this->assertSame(1, Address::query()->count());
        $this->assertTrue($user->addresses()->sole()->address->is($existing));
    }

    public function test_choosing_a_suggestion_fills_the_address_fields(): void
    {
        $this->actingAsAddressManager();
        $existing = Address::factory()->create(['street' => 'Rue du Lac 4', 'zip' => '1200', 'city' => 'Genève', 'latitude' => '46.2', 'longitude' => '6.1']);

        $this->manager(User::factory()->create())
            ->mountAction(TestAction::make('create')->table())
            ->fillForm(['street' => 'Rue du Lac 4 — 1200 Genève'])
            ->assertSchemaStateSet([
                'country_id' => (string) $existing->country_id,
                'street' => 'Rue du Lac 4',
                'zip' => '1200',
                'city' => 'Genève',
                'latitude' => '46.2000000',
                'longitude' => '6.1000000',
            ]);
    }

    public function test_editing_the_recipient_only_changes_the_entry(): void
    {
        $this->actingAsAddressManager();
        $user = User::factory()->create();
        $entry = UserAddress::factory()->for($user)->create(['first_name' => 'Jeanne']);
        $address = $entry->address;

        $this->manager($user)
            ->callAction(TestAction::make('edit')->table($entry), ['first_name' => 'Jeanne-Marie'])
            ->assertHasNoFormErrors();

        $this->assertSame('Jeanne-Marie', $entry->fresh()->first_name);
        $this->assertTrue($entry->fresh()->address->is($address));
        $this->assertSame(1, Address::query()->count());
    }

    public function test_editing_the_address_leaves_the_shared_one_intact(): void
    {
        $this->actingAsAddressManager();
        $user = User::factory()->create();
        $shared = Address::factory()->create(['street' => 'Rue du Lac 4', 'zip' => '1200', 'city' => 'Genève']);
        $entry = UserAddress::factory()->for($user)->for($shared)->create();
        $partner = UserAddress::factory()->for($shared)->create();

        $this->manager($user)
            ->callAction(TestAction::make('edit')->table($entry), ['street' => 'Rue du Rhône 10'])
            ->assertHasNoFormErrors();

        $this->assertSame('Rue du Lac 4', $shared->fresh()->street);
        $this->assertSame('Rue du Rhône 10', $entry->fresh()->address->street);
        $this->assertTrue($partner->fresh()->address->is($shared));
    }

    public function test_removing_only_deletes_the_entry(): void
    {
        $this->actingAsAddressManager();
        $user = User::factory()->create();
        $entry = UserAddress::factory()->for($user)->create();

        $this->manager($user)->callAction(TestAction::make('delete')->table($entry));

        $this->assertSame(0, $user->addresses()->count());
        $this->assertNotNull($entry->address->fresh());
    }

    public function test_the_tab_is_hidden_without_view_permission(): void
    {
        $this->actingAsUserWith(PermissionEnum::VIEW_USERS, PermissionEnum::UPDATE_USERS);

        $this->assertFalse(AddressesRelationManager::canViewForRecord(User::factory()->create(), EditUser::class));
    }
}
