<?php

namespace Modules\Location\Tests\Feature\Filament;

use Filament\Actions\Testing\TestAction;
use Livewire\Livewire;
use Modules\Auth\Enums\PermissionEnum;
use Modules\Auth\Tests\Feature\Filament\AuthFilamentTestCase;
use Modules\Location\Filament\Resources\CountryResource;
use Modules\Location\Filament\Resources\CountryResource\Pages\EditCountry;
use Modules\Location\Filament\Resources\CountryResource\RelationManagers\RegionsRelationManager;
use Modules\Location\Models\Country;
use Modules\Location\Models\Region;

/**
 * Les régions se gèrent dans la fiche de leur pays ; il n'y a plus de ressource
 * Régions autonome.
 */
class RegionsRelationManagerTest extends AuthFilamentTestCase
{
    private function manager(Country $country)
    {
        return Livewire::test(RegionsRelationManager::class, [
            'ownerRecord' => $country,
            'pageClass' => EditCountry::class,
        ]);
    }

    public function test_the_country_page_lists_only_its_regions(): void
    {
        $this->actingAsUserWith(PermissionEnum::VIEW_COUNTRIES, PermissionEnum::UPDATE_COUNTRIES, PermissionEnum::VIEW_REGIONS);
        $switzerland = Country::factory()->create();
        $vaud = Region::factory()->for($switzerland)->create();
        $bavaria = Region::factory()->create();

        $this->manager($switzerland)
            ->assertOk()
            ->assertCanSeeTableRecords([$vaud])
            ->assertCanNotSeeTableRecords([$bavaria])
            ->assertCountTableRecords(1);
    }

    public function test_the_regions_tab_is_hidden_without_view_permission(): void
    {
        $this->actingAsUserWith(PermissionEnum::VIEW_COUNTRIES, PermissionEnum::UPDATE_COUNTRIES);

        $this->assertFalse(RegionsRelationManager::canViewForRecord(Country::factory()->create(), EditCountry::class));
    }

    public function test_creates_a_region_in_the_country(): void
    {
        $this->actingAsUserWith(PermissionEnum::VIEW_COUNTRIES, PermissionEnum::UPDATE_COUNTRIES, PermissionEnum::VIEW_REGIONS, PermissionEnum::CREATE_REGIONS);
        $switzerland = Country::factory()->create();

        $this->manager($switzerland)
            ->callAction(TestAction::make('create')->table(), ['name' => ['fr' => 'Genève', 'en' => 'Geneva'], 'active' => true])
            ->assertHasNoFormErrors();

        $region = Region::query()->sole();
        $this->assertTrue($region->country->is($switzerland));
        $this->assertSame('Genève', $region->getTranslation('name', 'fr'));
        $this->assertSame('Geneva', $region->getTranslation('name', 'en'));
    }

    public function test_create_is_hidden_without_create_permission(): void
    {
        $this->actingAsUserWith(PermissionEnum::VIEW_COUNTRIES, PermissionEnum::UPDATE_COUNTRIES, PermissionEnum::VIEW_REGIONS);

        $this->manager(Country::factory()->create())->assertActionHidden(TestAction::make('create')->table());
    }

    public function test_edits_a_region_without_losing_the_other_language(): void
    {
        $this->actingAsUserWith(PermissionEnum::VIEW_COUNTRIES, PermissionEnum::UPDATE_COUNTRIES, PermissionEnum::VIEW_REGIONS, PermissionEnum::UPDATE_REGIONS);
        $region = Region::factory()->create(['name' => ['fr' => 'Genève', 'en' => 'Geneva']]);

        $this->manager($region->country)
            ->callAction(TestAction::make('edit')->table($region), ['name.fr' => 'République et canton de Genève'])
            ->assertHasNoFormErrors();

        $region->refresh();
        $this->assertSame('République et canton de Genève', $region->getTranslation('name', 'fr'));
        $this->assertSame('Geneva', $region->getTranslation('name', 'en'));
    }

    public function test_toggles_active_with_update_permission(): void
    {
        $this->actingAsUserWith(PermissionEnum::VIEW_COUNTRIES, PermissionEnum::UPDATE_COUNTRIES, PermissionEnum::VIEW_REGIONS, PermissionEnum::UPDATE_REGIONS);
        $region = Region::factory()->create();

        $this->manager($region->country)
            ->call('updateTableColumnState', 'active', (string) $region->getKey(), false);

        $this->assertFalse($region->refresh()->active);
    }

    public function test_there_is_no_standalone_regions_page_any_more(): void
    {
        $this->assertSame([RegionsRelationManager::class], CountryResource::getRelations());
        $this->assertFalse(class_exists('Modules\\Location\\Filament\\Resources\\RegionResource'));
    }
}
