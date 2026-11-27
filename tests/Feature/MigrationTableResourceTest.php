<?php

namespace Tests\Feature;

use Bza\MigrationViewer\Filament\Resources\MigrationTableResource;
use Bza\MigrationViewer\Filament\Resources\MigrationTableResource\Pages\ListMigrationTables;
use Bza\MigrationViewer\Filament\Resources\MigrationTableResource\Pages\ViewMigrationTable;
use Bza\MigrationViewer\Models\MigrationTable;
use Illuminate\Database\QueryException;
use Livewire\Livewire;
use Modules\Auth\Enums\PermissionEnum;
use Modules\Auth\Tests\Feature\Filament\AuthFilamentTestCase;

/**
 * Pages « Tables » du back-office (paquet bza/laravel-migration-viewer) : les
 * tables enregistrées dans migration_tables, une page de détail par table.
 */
class MigrationTableResourceTest extends AuthFilamentTestCase
{
    public function test_the_tables_are_stored_after_migrating(): void
    {
        // The test database was just migrated: the listener filled migration_tables.
        $users = MigrationTable::query()->where('name', 'users')->firstOrFail();

        $this->assertFalse($users->dropped);
        $this->assertContains('remember_token', array_column($users->columns, 'name'));
        $this->assertSame('0001_01_01_000000_create_users_table', $users->migrations[0]['migration']);
        $this->assertSame(0, $users->pending_count);
        $this->assertTrue(MigrationTable::query()->where('name', 'profile_menu_links')->value('dropped'));
    }

    public function test_a_table_name_is_unique(): void
    {
        $this->expectException(QueryException::class);

        MigrationTable::query()->create(['name' => 'users', 'columns' => [], 'migrations' => []]);
    }

    public function test_a_super_admin_lists_the_stored_tables(): void
    {
        $this->actingAsSuperAdmin();

        Livewire::test(ListMigrationTables::class)
            ->searchTable('users')
            ->assertCanSeeTableRecords(MigrationTable::query()->where('name', 'users')->get())
            ->assertSee('Présente');

        Livewire::test(ListMigrationTables::class)
            ->filterTable('dropped', true)
            ->assertCanSeeTableRecords(MigrationTable::query()->where('name', 'profile_menu_links')->get())
            ->assertCanNotSeeTableRecords(MigrationTable::query()->where('name', 'users')->get());
    }

    public function test_the_detail_page_shows_the_files_and_the_current_columns(): void
    {
        $this->actingAsSuperAdmin();

        $this->get(MigrationTableResource::getUrl('view', ['record' => 'users']))
            ->assertOk()
            ->assertSee('Fichiers de migration')
            ->assertSee('0001_01_01_000000_create_users_table')
            ->assertSee('2026_12_07_200000_add_identified_at_to_users')
            ->assertSee('Exécutée')
            ->assertSee('Champs actuels')
            ->assertSee('remember_token')
            ->assertSee('dateTime, nullable');
    }

    public function test_a_dropped_table_has_no_current_column(): void
    {
        $this->actingAsSuperAdmin();

        Livewire::test(ViewMigrationTable::class, ['record' => 'profile_menu_links'])
            ->assertSee('Supprimée')
            ->assertSee('Aucun champ');
    }

    public function test_the_tables_can_be_synchronized_from_the_list(): void
    {
        $this->actingAsSuperAdmin();
        MigrationTable::query()->delete();
        MigrationTable::query()->create(['name' => 'gone_table', 'columns' => [], 'migrations' => []]);

        Livewire::test(ListMigrationTables::class)
            ->callAction('sync')
            ->assertNotified();

        $this->assertTrue(MigrationTable::query()->where('name', 'users')->exists());
        $this->assertFalse(MigrationTable::query()->where('name', 'gone_table')->exists());
    }

    public function test_an_admin_without_the_super_admin_role_is_refused(): void
    {
        $this->actingAsUserWith(PermissionEnum::UPDATE_SETTINGS);

        $this->get(MigrationTableResource::getUrl('index'))->assertForbidden();
        $this->get(MigrationTableResource::getUrl('view', ['record' => 'users']))->assertForbidden();
    }
}
