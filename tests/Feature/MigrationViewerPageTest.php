<?php

namespace Tests\Feature;

use Bza\MigrationViewer\Filament\Pages\MigrationViewerPage;
use Bza\MigrationViewer\Filament\Resources\MigrationTableResource;
use Livewire\Livewire;
use Modules\Auth\Enums\PermissionEnum;
use Modules\Auth\Tests\Feature\Filament\AuthFilamentTestCase;

/**
 * Page « Migrations » du back-office (paquet bza/laravel-migration-viewer) :
 * fichiers de migration regroupés par table, modules compris, réservée aux
 * super administrateurs.
 */
class MigrationViewerPageTest extends AuthFilamentTestCase
{
    public function test_a_super_admin_sees_the_migrations_of_a_table(): void
    {
        $this->actingAsSuperAdmin();

        Livewire::test(MigrationViewerPage::class)
            ->filterTable('table', 'users')
            ->assertCanSeeTableRecords([
                '0001_01_01_000000_create_users_table#users',
                '2026_11_22_090000_add_soft_deletes_to_users#users',
            ])
            ->assertSee('Création')
            ->assertSee('Exécutée')
            ->assertSee('+ deleted_at timestamp, nullable');
    }

    public function test_the_migrations_can_be_filtered_by_module(): void
    {
        $this->actingAsSuperAdmin();

        Livewire::test(MigrationViewerPage::class)
            ->filterTable('origin', 'module')
            ->filterTable('module', 'Location')
            ->assertCanSeeTableRecords(['2026_11_17_100000_create_countries_table#countries'])
            ->assertCanNotSeeTableRecords(['0001_01_01_000000_create_users_table#users'])
            ->assertSee('Module Location');
    }

    public function test_the_module_filter_only_shows_with_the_module_origin(): void
    {
        $this->actingAsSuperAdmin();

        $page = Livewire::test(MigrationViewerPage::class)->assertDontSeeHtml('tableFilters.module.value');
        $page->set('tableFilters.origin.value', 'module')->assertSeeHtml('tableFilters.module.value');

        $page->set('tableFilters.module.value', 'Location')
            ->set('tableFilters.origin.value', 'application')
            ->assertDontSeeHtml('tableFilters.module.value')
            ->assertSet('tableFilters.module.value', null);
    }

    public function test_a_dropped_table_says_so(): void
    {
        $this->actingAsSuperAdmin();

        Livewire::test(MigrationViewerPage::class)
            ->filterTable('table', 'profile_menu_links')
            ->assertCanSeeTableRecords(['2026_11_22_110000_drop_profile_menu_links_table#profile_menu_links'])
            ->assertSee('Suppression')
            ->assertSee('Supprimée par une migration ultérieure');
    }

    public function test_a_row_links_to_its_table_detail_page(): void
    {
        $this->actingAsSuperAdmin();

        Livewire::test(MigrationViewerPage::class)
            ->filterTable('table', 'users')
            ->assertSeeHtml(MigrationTableResource::getUrl('view', ['record' => 'users']));
    }

    public function test_an_admin_without_the_super_admin_role_is_refused(): void
    {
        $this->actingAsUserWith(PermissionEnum::UPDATE_SETTINGS);

        $this->get(MigrationViewerPage::getUrl())->assertForbidden();
    }

    public function test_the_page_is_in_the_settings_group(): void
    {
        $this->actingAsSuperAdmin();

        $this->assertSame('Paramètres', MigrationViewerPage::getNavigationGroup());
        $this->get(MigrationViewerPage::getUrl())->assertOk()->assertSee('Migrations');
    }
}
