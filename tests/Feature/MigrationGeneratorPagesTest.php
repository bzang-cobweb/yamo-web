<?php

namespace Tests\Feature;

use Bza\MigrationViewer\Filament\Resources\MigrationTableResource;
use Bza\MigrationViewer\Filament\Resources\MigrationTableResource\Pages\CreateMigrationTable;
use Bza\MigrationViewer\Filament\Resources\MigrationTableResource\Pages\EditMigrationTable;
use Bza\MigrationViewer\Filament\Resources\MigrationTableResource\Pages\ListMigrationTables;
use Bza\MigrationViewer\Filament\Resources\MigrationTableResource\Pages\ViewMigrationTable;
use Bza\MigrationViewer\Generator\ColumnType;
use Bza\MigrationViewer\Generator\MigrationGenerator;
use Bza\MigrationViewer\Generator\NewColumn;
use Bza\MigrationViewer\Generator\NewTable;
use Bza\MigrationViewer\MigrationInspector;
use Bza\MigrationViewer\Models\MigrationTable;
use Filament\Actions\Testing\TestAction;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Modules\Auth\Enums\PermissionEnum;
use Modules\Auth\Tests\Feature\Filament\AuthFilamentTestCase;

/**
 * Pages « Nouvelle table » et « Modifier la table » (paquet bza/laravel-migration-viewer) :
 * le formulaire devient un seul fichier de migration dans le dossier configuré, jamais exécuté.
 */
class MigrationGeneratorPagesTest extends AuthFilamentTestCase
{
    private string $directory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->directory = storage_path('framework/testing/migration-generator-'.uniqid());
        config(['migration-viewer.generator.enabled' => true, 'migration-viewer.generator.path' => $this->directory]);
    }

    protected function tearDown(): void
    {
        foreach (glob($this->directory.'/*.php') ?: [] as $file) {
            unlink($file);
        }

        if (is_dir($this->directory)) {
            rmdir($this->directory);
        }

        parent::tearDown();
    }

    /**
     * @return list<string>
     */
    private function files(string $pattern = '*'): array
    {
        return glob($this->directory.'/'.$pattern.'.php') ?: [];
    }

    public function test_the_create_page_writes_a_pending_create_migration(): void
    {
        $this->actingAsSuperAdmin();

        Livewire::test(CreateMigrationTable::class)
            ->fillForm([
                'name' => 'invoices',
                'id' => true,
                'timestamps' => true,
                'soft_deletes' => false,
                'columns' => [
                    ['name' => 'number', 'type' => 'string', 'length' => '20', 'unique' => true],
                    ['name' => 'amount', 'type' => 'decimal', 'total' => '10', 'places' => '2', 'default' => '0'],
                    ['name' => 'user_id', 'type' => 'foreignId', 'nullable' => true, 'on_delete' => 'set null'],
                ],
            ])
            ->call('save')
            ->assertHasNoFormErrors()
            ->assertNotified('Migration générée')
            ->assertRedirect(MigrationTableResource::getUrl('view', ['record' => 'invoices']));

        $this->assertCount(1, $this->files('*_create_invoices_table'));
        $this->assertStringContainsString("\$table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();", file_get_contents($this->files()[0]));

        // Stored at once, as a pending migration: the database itself is untouched.
        $invoices = MigrationTable::query()->where('name', 'invoices')->firstOrFail();
        $this->assertSame(['id', 'number', 'amount', 'user_id', 'created_at', 'updated_at'], array_column($invoices->columns, 'name'));
        $this->assertSame(1, $invoices->pending_count);
        $this->assertSame('generated', $invoices->migrations[0]['origin']);
        $this->assertFalse(Schema::hasTable('invoices'));
    }

    public function test_the_create_page_refuses_an_existing_table_a_bad_name_or_a_column_of_the_options(): void
    {
        $this->actingAsSuperAdmin();

        Livewire::test(CreateMigrationTable::class)
            ->fillForm(['name' => 'users', 'columns' => []])
            ->call('save')
            ->assertHasFormErrors(['name']);

        Livewire::test(CreateMigrationTable::class)
            ->fillForm(['name' => 'Bad Name', 'columns' => []])
            ->call('save')
            ->assertHasFormErrors(['name']);

        Livewire::test(CreateMigrationTable::class)
            ->fillForm(['name' => 'invoices', 'timestamps' => true, 'columns' => [['name' => 'created_at', 'type' => 'timestamp']]])
            ->call('save')
            ->assertNotified('Migration non générée')
            ->assertNoRedirect();

        $this->assertSame([], $this->files());
    }

    public function test_the_edit_page_starts_from_the_current_columns(): void
    {
        $this->actingAsSuperAdmin();

        $columns = Livewire::test(EditMigrationTable::class, ['record' => 'users'])
            ->assertFormSet(['name' => 'users'])
            ->get('data.columns');

        $byName = collect($columns)->keyBy('original');
        // The auto-increment key can only be kept or dropped.
        $this->assertTrue($byName['id']['locked']);
        $this->assertSame(['locked' => false, 'type' => 'string', 'length' => 255, 'nullable' => true, 'unique' => true], array_intersect_key($byName['email'] ?? [], array_flip(['locked', 'type', 'length', 'nullable', 'unique'])));
        $this->assertSame(['type' => 'foreignId', 'references' => 'countries', 'on_delete' => 'set null'], array_intersect_key($byName['country_id'], array_flip(['type', 'references', 'on_delete'])));
    }

    public function test_every_change_of_the_edit_page_goes_into_one_migration(): void
    {
        $this->actingAsSuperAdmin();

        $page = Livewire::test(EditMigrationTable::class, ['record' => 'users']);
        $columns = collect($page->get('data.columns'))
            // Removed.
            ->reject(fn (array $item): bool => $item['original'] === 'description')
            // Renamed and changed.
            ->map(fn (array $item): array => $item['original'] === 'gender' ? [...$item, 'name' => 'sex', 'length' => '8'] : $item)
            ->values()
            ->push(['original' => null, 'locked' => false, 'name' => 'nickname', 'type' => 'string', 'nullable' => true])
            ->all();

        $page->fillForm(['name' => 'users', 'columns' => $columns])
            ->call('save')
            ->assertHasNoFormErrors()
            ->assertNotified('Migration générée')
            ->assertRedirect(MigrationTableResource::getUrl('view', ['record' => 'users']));

        $this->assertCount(1, $this->files());
        $code = file_get_contents($this->files('*_alter_users_table')[0]);
        $this->assertStringContainsString("\$table->dropColumn('description');", $code);
        $this->assertStringContainsString("\$table->renameColumn('gender', 'sex');", $code);
        $this->assertStringContainsString("\$table->string('sex', 8)->nullable()->change();", $code);
        $this->assertStringContainsString("\$table->string('nickname')->nullable();", $code);

        $users = MigrationTable::query()->where('name', 'users')->firstOrFail();
        $names = array_column($users->columns, 'name');
        $this->assertNotContains('description', $names);
        $this->assertNotContains('gender', $names);
        $this->assertContains('sex', $names);
        $this->assertSame('nickname', end($names));
        $this->assertSame(1, $users->pending_count);
    }

    public function test_a_field_is_added_from_a_slide_over(): void
    {
        $this->actingAsSuperAdmin();

        $page = Livewire::test(EditMigrationTable::class, ['record' => 'users'])
            ->callAction(TestAction::make('add')->schemaComponent('columns', schema: 'form'), ['name' => 'nickname', 'type' => 'string', 'length' => '30', 'nullable' => true])
            ->assertHasNoFormComponentActionErrors();

        $added = collect($page->get('data.columns'))->firstWhere('name', 'nickname');
        $this->assertNull($added['original']);
        $this->assertFalse($added['locked']);
        $this->assertSame('string', $added['type']);
        $this->assertEquals(30, $added['length']);
        $this->assertTrue($added['nullable']);

        // A foreign id points to an existing table; another value is refused.
        $page->callAction(TestAction::make('add')->schemaComponent('columns', schema: 'form'), ['name' => 'manager_id', 'type' => 'foreignId', 'references' => 'not_a_table'])
            ->assertHasFormComponentActionErrors(['references']);
        $page->callAction(TestAction::make('add')->schemaComponent('columns', schema: 'form'), ['name' => 'manager_id', 'type' => 'foreignId', 'references' => 'users', 'nullable' => true, 'on_delete' => 'set null'])
            ->assertHasNoFormComponentActionErrors();
        $this->assertSame('users', collect($page->get('data.columns'))->firstWhere('name', 'manager_id')['references']);

        // A name already in the list is refused in the slide-over.
        $page->callAction(TestAction::make('add')->schemaComponent('columns', schema: 'form'), ['name' => 'email', 'type' => 'string'])
            ->assertHasFormComponentActionErrors(['name']);

        $page->call('save')->assertNotified('Migration générée');
        $code = file_get_contents($this->files('*_alter_users_table')[0]);
        $this->assertStringContainsString("\$table->string('nickname', 30)->nullable();", $code);
        $this->assertStringContainsString("\$table->foreignId('manager_id')->nullable()->constrained('users')->nullOnDelete();", $code);
    }

    public function test_a_field_is_edited_from_a_slide_over(): void
    {
        $this->actingAsSuperAdmin();

        $page = Livewire::test(EditMigrationTable::class, ['record' => 'users']);
        $key = collect($page->get('data.columns'))->search(fn (array $item): bool => $item['original'] === 'gender');
        $edit = fn (): TestAction => TestAction::make('editColumn')->schemaComponent('columns', schema: 'form')->arguments(['item' => $key]);

        // Starts from the column; another column's name is refused.
        $page->mountAction($edit())
            ->assertSchemaStateSet(['name' => 'gender', 'type' => 'string', 'length' => 16, 'nullable' => true], 'mountedActionSchema0')
            ->callMountedAction()
            ->assertHasNoFormComponentActionErrors();
        $page->callAction($edit(), ['name' => 'email', 'type' => 'string'])->assertHasFormComponentActionErrors(['name']);

        $page->callAction($edit(), ['name' => 'sex', 'type' => 'string', 'length' => '8', 'nullable' => true])
            ->assertHasNoFormComponentActionErrors();

        $item = $page->get('data.columns')[$key];
        $this->assertSame(['gender', 'sex'], [$item['original'], $item['name']]);
        $this->assertEquals(8, $item['length']);

        // The id cannot be edited: kept or dropped only.
        $id = collect($page->get('data.columns'))->search(fn (array $item): bool => $item['original'] === 'id');
        $page->assertActionHidden(TestAction::make('editColumn')->schemaComponent('columns', schema: 'form')->arguments(['item' => $id]));

        $page->call('save')->assertNotified('Migration générée');
        $code = file_get_contents($this->files('*_alter_users_table')[0]);
        $this->assertStringContainsString("\$table->renameColumn('gender', 'sex');", $code);
        $this->assertStringContainsString("\$table->string('sex', 8)->nullable()->change();", $code);
    }

    public function test_migrate_the_table_runs_only_its_pending_files_then_migrate_all_runs_the_rest(): void
    {
        $this->actingAsSuperAdmin();
        $generator = app(MigrationGenerator::class);
        $generator->createTable(new NewTable('invoices', [new NewColumn('number', ColumnType::String)]));
        $generator->addColumns('users', [new NewColumn('nickname', ColumnType::String, nullable: true)]);
        // A new request in real life: the replays memoized before the files were written are gone.
        app()->forgetInstance(MigrationInspector::class);

        Livewire::test(EditMigrationTable::class, ['record' => 'users'])
            ->assertActionEnabled('migrateTable')
            ->callAction('migrateTable')
            ->assertNotified('Migrations exécutées')
            ->assertRedirect(MigrationTableResource::getUrl('edit', ['record' => 'users']));

        $this->assertTrue(Schema::hasColumn('users', 'nickname'));
        $this->assertFalse(Schema::hasTable('invoices'));
        $this->assertSame(0, MigrationTable::query()->where('name', 'users')->value('pending_count'));
        $this->assertSame(1, MigrationTable::query()->where('name', 'invoices')->value('pending_count'));

        app()->forgetInstance(MigrationInspector::class);

        Livewire::test(EditMigrationTable::class, ['record' => 'users'])
            ->assertActionDisabled('migrateTable')
            ->assertActionEnabled('migrateAll')
            ->callAction('migrateAll')
            ->assertNotified('Migrations exécutées');

        $this->assertSame(['id', 'number', 'created_at', 'updated_at'], Schema::getColumnListing('invoices'));

        app()->forgetInstance(MigrationInspector::class);
        Livewire::test(EditMigrationTable::class, ['record' => 'users'])->assertActionDisabled('migrateAll');
    }

    public function test_an_edit_that_changes_nothing_writes_nothing(): void
    {
        $this->actingAsSuperAdmin();

        Livewire::test(EditMigrationTable::class, ['record' => 'users'])
            ->call('save')
            ->assertNotified('Migration non générée')
            ->assertNoRedirect();

        $this->assertSame([], $this->files());
    }

    public function test_the_pages_are_reached_from_the_list_and_the_detail(): void
    {
        $this->actingAsSuperAdmin();

        Livewire::test(ListMigrationTables::class)->assertActionHasUrl('create', MigrationTableResource::getUrl('create'));
        Livewire::test(ViewMigrationTable::class, ['record' => 'users'])->assertActionHasUrl('edit', MigrationTableResource::getUrl('edit', ['record' => 'users']));
        Livewire::test(ViewMigrationTable::class, ['record' => 'profile_menu_links'])->assertActionHidden('edit');
        $this->get(MigrationTableResource::getUrl('edit', ['record' => 'profile_menu_links']))->assertForbidden();
    }

    public function test_the_pages_are_closed_when_the_generator_is_disabled_or_to_other_admins(): void
    {
        config(['migration-viewer.generator.enabled' => false]);
        $this->actingAsSuperAdmin();

        Livewire::test(ListMigrationTables::class)->assertActionHidden('create');
        Livewire::test(ViewMigrationTable::class, ['record' => 'users'])->assertActionHidden('edit');
        $this->get(MigrationTableResource::getUrl('create'))->assertForbidden();
        $this->get(MigrationTableResource::getUrl('edit', ['record' => 'users']))->assertForbidden();

        config(['migration-viewer.generator.enabled' => true]);
        $this->actingAsUserWith(PermissionEnum::UPDATE_SETTINGS);
        $this->get(MigrationTableResource::getUrl('create'))->assertForbidden();
    }
}
