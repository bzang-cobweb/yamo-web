<?php

namespace Modules\Auth\Tests\Feature\Filament;

use Filament\Actions\Testing\TestAction;
use Livewire\Livewire;
use Modules\Auth\Enums\PermissionEnum;
use Modules\Auth\Filament\Resources\UserResource\Pages\EditUser;
use Modules\Auth\Filament\Resources\UserResource\RelationManagers\AddressesRelationManager;
use Modules\Auth\Models\User;
use Modules\Location\Models\Address;
use Modules\Location\Models\Country;

/**
 * Les adresses d'un utilisateur, sur le pivot : ajouter ou modifier reprend
 * l'adresse identique au lieu de la dupliquer, retirer ne supprime que le pivot
 * (décision 2026-11-18-adresses-liste-generique).
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

    public function test_lists_only_the_user_addresses(): void
    {
        $this->actingAsAddressManager();
        $user = User::factory()->create();
        $mine = Address::factory()->create();
        $user->addresses()->attach($mine);
        $other = Address::factory()->create();

        $this->manager($user)
            ->assertOk()
            ->assertCanSeeTableRecords([$mine])
            ->assertCanNotSeeTableRecords([$other]);
    }

    public function test_adding_a_new_address_creates_it_and_the_pivot(): void
    {
        $this->actingAsAddressManager();
        $user = User::factory()->create();
        $country = Country::factory()->create();

        $this->manager($user)
            ->callAction(TestAction::make('create')->table(), [
                'country_id' => (string) $country->getKey(), 'street' => 'Rue du Lac 4', 'zip' => '1200', 'city' => 'Genève',
            ])
            ->assertHasNoFormErrors();

        $address = Address::query()->sole();
        $this->assertTrue($user->addresses()->sole()->is($address));
        $this->assertTrue($address->country->is($country));
    }

    public function test_adding_an_existing_address_only_creates_the_pivot(): void
    {
        $this->actingAsAddressManager();
        $user = User::factory()->create();
        $existing = Address::factory()->create(['street' => 'Rue du Lac 4', 'zip' => '1200', 'city' => 'Genève']);

        $this->manager($user)
            ->callAction(TestAction::make('create')->table(), [
                'country_id' => (string) $existing->country_id, 'street' => 'RUE DU LAC 4', 'zip' => '1200', 'city' => 'genève',
            ])
            ->assertHasNoFormErrors();

        $this->assertSame(1, Address::query()->count());
        $this->assertTrue($user->addresses()->sole()->is($existing));
    }

    public function test_editing_moves_the_pivot_and_leaves_the_shared_address_intact(): void
    {
        $this->actingAsAddressManager();
        $user = User::factory()->create();
        $partner = User::factory()->create();
        $shared = Address::factory()->create(['street' => 'Rue du Lac 4', 'zip' => '1200', 'city' => 'Genève']);
        $user->addresses()->attach($shared);
        $partner->addresses()->attach($shared);

        $this->manager($user)
            ->callAction(TestAction::make('edit')->table($shared), ['street' => 'Rue du Rhône 10'])
            ->assertHasNoFormErrors();

        $this->assertSame('Rue du Lac 4', $shared->fresh()->street);
        $this->assertSame('Rue du Rhône 10', $user->addresses()->sole()->street);
        $this->assertTrue($partner->addresses()->sole()->is($shared));
    }

    public function test_removing_only_detaches(): void
    {
        $this->actingAsAddressManager();
        $user = User::factory()->create();
        $address = Address::factory()->create();
        $user->addresses()->attach($address);

        $this->manager($user)->callAction(TestAction::make('detach')->table($address));

        $this->assertSame(0, $user->addresses()->count());
        $this->assertNotNull($address->fresh());
    }

    public function test_the_tab_is_hidden_without_view_permission(): void
    {
        $this->actingAsUserWith(PermissionEnum::VIEW_USERS, PermissionEnum::UPDATE_USERS);

        $this->assertFalse(AddressesRelationManager::canViewForRecord(User::factory()->create(), EditUser::class));
    }
}
