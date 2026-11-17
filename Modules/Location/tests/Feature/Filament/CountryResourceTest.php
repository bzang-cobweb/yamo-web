<?php

namespace Modules\Location\Tests\Feature\Filament;

use Livewire\Livewire;
use Modules\Auth\Enums\PermissionEnum;
use Modules\Auth\Tests\Feature\Filament\AuthFilamentTestCase;
use Modules\Location\Filament\Resources\CountryResource\Pages\CreateCountry;
use Modules\Location\Filament\Resources\CountryResource\Pages\EditCountry;
use Modules\Location\Filament\Resources\CountryResource\Pages\ListCountries;
use Modules\Location\Models\Country;
use Modules\Location\Models\Region;

class CountryResourceTest extends AuthFilamentTestCase
{
    public function test_list_is_forbidden_without_view_permission(): void
    {
        $this->actingAsUserWith();

        Livewire::test(ListCountries::class)->assertForbidden();
    }

    public function test_list_shows_searches_and_filters_countries(): void
    {
        $this->actingAsUserWith(PermissionEnum::VIEW_COUNTRIES);
        $active = Country::factory()->create(['name' => ['fr' => 'Allemagne', 'en' => 'Germany'], 'native_name' => 'Deutschland']);
        $inactive = Country::factory()->inactive()->create();

        Livewire::test(ListCountries::class)
            ->assertOk()
            ->assertCanSeeTableRecords([$active, $inactive])
            ->searchTable('Deutsch')
            ->assertCanSeeTableRecords([$active])
            ->assertCanNotSeeTableRecords([$inactive])
            ->searchTable(null)
            ->filterTable('active', false)
            ->assertCanSeeTableRecords([$inactive])
            ->assertCanNotSeeTableRecords([$active]);
    }

    public function test_toggles_active_with_update_permission(): void
    {
        $this->actingAsUserWith(PermissionEnum::VIEW_COUNTRIES, PermissionEnum::UPDATE_COUNTRIES);
        $country = Country::factory()->create();

        Livewire::test(ListCountries::class)
            ->call('updateTableColumnState', 'active', (string) $country->getKey(), false);

        $this->assertFalse($country->refresh()->active);
    }

    public function test_toggle_is_refused_without_update_permission(): void
    {
        $this->actingAsUserWith(PermissionEnum::VIEW_COUNTRIES);
        $country = Country::factory()->create();

        Livewire::test(ListCountries::class)
            ->call('updateTableColumnState', 'active', (string) $country->getKey(), false);

        $this->assertTrue($country->refresh()->active);
    }

    public function test_creates_a_country_with_both_translations(): void
    {
        $this->actingAsUserWith(PermissionEnum::VIEW_COUNTRIES, PermissionEnum::CREATE_COUNTRIES);

        Livewire::test(CreateCountry::class)
            ->fillForm(['code' => 'qx', 'name' => 'Pays test', 'native_name' => 'Testland', 'dial_code' => '+999', 'active' => true])
            ->set('activeLocale', 'en')
            ->fillForm(['name' => 'Test country'])
            ->call('create')
            ->assertHasNoFormErrors();

        $country = Country::query()->where('code', 'QX')->firstOrFail();
        $this->assertSame('Pays test', $country->getTranslation('name', 'fr'));
        $this->assertSame('Test country', $country->getTranslation('name', 'en'));
        $this->assertSame('+999', $country->dial_code);
    }

    public function test_create_is_forbidden_without_create_permission(): void
    {
        $this->actingAsUserWith(PermissionEnum::VIEW_COUNTRIES);

        Livewire::test(CreateCountry::class)->assertForbidden();
    }

    public function test_edits_the_dial_code_and_one_translation(): void
    {
        $this->actingAsUserWith(PermissionEnum::VIEW_COUNTRIES, PermissionEnum::UPDATE_COUNTRIES);
        $country = Country::factory()->create(['name' => ['fr' => 'Suisse', 'en' => 'Switzerland']]);

        Livewire::test(EditCountry::class, ['record' => $country->getKey()])
            ->assertSchemaStateSet(['name' => 'Suisse'])
            ->fillForm(['dial_code' => '+41', 'name' => 'Confédération suisse'])
            ->call('save')
            ->assertHasNoFormErrors();

        $country->refresh();
        $this->assertSame('+41', $country->dial_code);
        $this->assertSame('Confédération suisse', $country->getTranslation('name', 'fr'));
        $this->assertSame('Switzerland', $country->getTranslation('name', 'en'));
    }

    public function test_rejects_a_dial_code_without_plus(): void
    {
        $this->actingAsUserWith(PermissionEnum::VIEW_COUNTRIES, PermissionEnum::UPDATE_COUNTRIES);
        $country = Country::factory()->create();

        Livewire::test(EditCountry::class, ['record' => $country->getKey()])
            ->fillForm(['dial_code' => '41'])
            ->call('save')
            ->assertHasFormErrors(['dial_code']);
    }

    public function test_edit_is_forbidden_without_update_permission(): void
    {
        $this->actingAsUserWith(PermissionEnum::VIEW_COUNTRIES);
        $country = Country::factory()->create();

        Livewire::test(EditCountry::class, ['record' => $country->getKey()])->assertForbidden();
    }

    public function test_list_shows_the_number_of_regions_of_each_country(): void
    {
        $this->actingAsUserWith(PermissionEnum::VIEW_COUNTRIES);
        $country = Country::factory()->create();
        Region::factory()->count(3)->create(['country_id' => $country->getKey()]);
        $empty = Country::factory()->create();

        Livewire::test(ListCountries::class)
            ->assertTableColumnVisible('regions_count')
            ->assertTableColumnStateSet('regions_count', 3, $country)
            ->assertTableColumnStateSet('regions_count', 0, $empty);
    }
}
