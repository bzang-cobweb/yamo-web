<?php

namespace Tests\Feature;

use App\Filament\Resources\SettingResource;
use Livewire\Livewire;
use Modules\Auth\Enums\PermissionEnum;
use Modules\Auth\Filament\Resources\PermissionResource;
use Modules\Auth\Filament\Resources\PermissionResource\Pages\ViewPermission;
use Modules\Auth\Filament\Resources\RoleResource\Pages\ListRoles;
use Modules\Auth\Filament\Resources\UserResource;
use Modules\Auth\Filament\Resources\UserResource\Pages\EditUser;
use Modules\Auth\Filament\Resources\UserResource\Pages\ListUsers;
use Modules\Auth\Models\Permission;
use Modules\Auth\Models\User;
use Modules\Auth\Tests\Feature\Filament\AuthFilamentTestCase;
use Modules\Location\Filament\Resources\CountryResource;
use Modules\Location\Filament\Resources\CountryResource\Pages\EditCountry;
use Modules\Location\Filament\Resources\CountryResource\Pages\ListCountries;
use Modules\Location\Models\Country;

/**
 * Menu et fil d'Ariane du back-office : une fiche affiche `<Ressource> > <titre>`,
 * une liste n'a pas de fil, les pays sont rangés dans « Paramètres ».
 */
class BackOfficeNavigationTest extends AuthFilamentTestCase
{
    public function test_an_edit_page_shows_the_resource_then_the_record_title(): void
    {
        $this->actingAsUserWith(PermissionEnum::VIEW_USERS, PermissionEnum::UPDATE_USERS);
        $user = User::factory()->create(['first_name' => 'Jeanne', 'last_name' => 'Dupont']);

        $breadcrumbs = Livewire::test(EditUser::class, ['record' => $user->getKey()])->instance()->getBreadcrumbs();

        $this->assertSame([UserResource::getUrl('index') => UserResource::getBreadcrumb(), 0 => 'Jeanne Dupont'], $breadcrumbs);
    }

    public function test_a_country_page_shows_its_translated_name(): void
    {
        $this->actingAsUserWith(PermissionEnum::VIEW_COUNTRIES, PermissionEnum::UPDATE_COUNTRIES);
        $country = Country::factory()->create(['name' => ['fr' => 'Suisse', 'en' => 'Switzerland'], 'native_name' => 'Schweiz']);

        $breadcrumbs = Livewire::test(EditCountry::class, ['record' => $country->getKey()])->instance()->getBreadcrumbs();

        $this->assertSame([CountryResource::getUrl('index') => CountryResource::getBreadcrumb(), 0 => 'Suisse (Schweiz)'], $breadcrumbs);
    }

    public function test_a_view_page_shows_the_resource_then_the_record_title(): void
    {
        $this->actingAsUserWith(PermissionEnum::VIEW_PERMISSIONS);
        $permission = Permission::query()->firstOrFail();

        $breadcrumbs = Livewire::test(ViewPermission::class, ['record' => $permission->getKey()])->instance()->getBreadcrumbs();

        $this->assertSame([PermissionResource::getUrl('index') => PermissionResource::getBreadcrumb(), 0 => $permission->name], $breadcrumbs);
    }

    public function test_list_pages_have_no_breadcrumbs(): void
    {
        $this->actingAsUserWith(PermissionEnum::VIEW_USERS, PermissionEnum::VIEW_ROLES, PermissionEnum::VIEW_COUNTRIES);

        foreach ([ListUsers::class, ListRoles::class, ListCountries::class] as $page) {
            $this->assertSame([], Livewire::test($page)->instance()->getBreadcrumbs(), $page);
        }
    }

    public function test_countries_are_in_the_settings_group(): void
    {
        $this->assertSame(SettingResource::getNavigationGroup(), CountryResource::getNavigationGroup());
        $this->assertSame('Paramètres', CountryResource::getNavigationGroup());
    }
}
