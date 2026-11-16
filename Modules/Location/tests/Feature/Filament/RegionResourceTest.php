<?php

namespace Modules\Location\Tests\Feature\Filament;

use Livewire\Livewire;
use Modules\Auth\Enums\PermissionEnum;
use Modules\Auth\Tests\Feature\Filament\AuthFilamentTestCase;
use Modules\Location\Filament\Resources\RegionResource\Pages\CreateRegion;
use Modules\Location\Filament\Resources\RegionResource\Pages\EditRegion;
use Modules\Location\Filament\Resources\RegionResource\Pages\ListRegions;
use Modules\Location\Models\Country;
use Modules\Location\Models\Region;

class RegionResourceTest extends AuthFilamentTestCase
{
    public function test_list_is_forbidden_without_view_permission(): void
    {
        $this->actingAsUserWith();

        Livewire::test(ListRegions::class)->assertForbidden();
    }

    public function test_list_filters_regions_by_country(): void
    {
        $this->actingAsUserWith(PermissionEnum::VIEW_REGIONS);
        $vaud = Region::factory()->create();
        $bavaria = Region::factory()->create();

        Livewire::test(ListRegions::class)
            ->assertOk()
            ->assertCanSeeTableRecords([$vaud, $bavaria])
            ->filterTable('country_id', $vaud->country_id)
            ->assertCanSeeTableRecords([$vaud])
            ->assertCanNotSeeTableRecords([$bavaria]);
    }

    public function test_toggles_active_with_update_permission(): void
    {
        $this->actingAsUserWith(PermissionEnum::VIEW_REGIONS, PermissionEnum::UPDATE_REGIONS);
        $region = Region::factory()->create();

        Livewire::test(ListRegions::class)
            ->call('updateTableColumnState', 'active', (string) $region->getKey(), false);

        $this->assertFalse($region->refresh()->active);
    }

    public function test_creates_a_region_of_a_country(): void
    {
        $this->actingAsUserWith(PermissionEnum::VIEW_REGIONS, PermissionEnum::CREATE_REGIONS);
        $country = Country::factory()->create();

        Livewire::test(CreateRegion::class)
            ->fillForm(['country_id' => $country->getKey(), 'name' => 'Genève', 'active' => true])
            ->call('create')
            ->assertHasNoFormErrors();

        $region = Region::query()->sole();
        $this->assertTrue($region->country->is($country));
        $this->assertSame('Genève', $region->getTranslation('name', 'fr'));
    }

    public function test_create_requires_a_country(): void
    {
        $this->actingAsUserWith(PermissionEnum::VIEW_REGIONS, PermissionEnum::CREATE_REGIONS);

        Livewire::test(CreateRegion::class)
            ->fillForm(['name' => 'Genève'])
            ->call('create')
            ->assertHasFormErrors(['country_id' => 'required']);
    }

    public function test_edits_a_region(): void
    {
        $this->actingAsUserWith(PermissionEnum::VIEW_REGIONS, PermissionEnum::UPDATE_REGIONS);
        $region = Region::factory()->create(['name' => ['fr' => 'Genève', 'en' => 'Geneva']]);

        Livewire::test(EditRegion::class, ['record' => $region->getKey()])
            ->fillForm(['name' => 'République et canton de Genève'])
            ->call('save')
            ->assertHasNoFormErrors();

        $region->refresh();
        $this->assertSame('République et canton de Genève', $region->getTranslation('name', 'fr'));
        $this->assertSame('Geneva', $region->getTranslation('name', 'en'));
    }

    public function test_edit_is_forbidden_without_update_permission(): void
    {
        $this->actingAsUserWith(PermissionEnum::VIEW_REGIONS);
        $region = Region::factory()->create();

        Livewire::test(EditRegion::class, ['record' => $region->getKey()])->assertForbidden();
    }
}
