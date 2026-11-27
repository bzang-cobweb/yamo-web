<?php

namespace Bza\MigrationViewer\Tests;

use Bza\MigrationViewer\ChangeKind;
use Bza\MigrationViewer\Data\ChangeDetail;
use Bza\MigrationViewer\Data\MigrationQuery;
use Bza\MigrationViewer\Data\TableChange;
use Bza\MigrationViewer\MigrationInspector;
use Bza\MigrationViewer\Operation;
use Bza\MigrationViewer\Origin;
use Bza\MigrationViewer\Replay\MigrationReplayer;
use Bza\MigrationViewer\Sources\ApplicationMigrationSource;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\TestCase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * The core replays the fixture migrations inside the host application (its
 * database connections), never the application's own migrations.
 */
class MigrationInspectorTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('migration_viewer_guard', function (Blueprint $table) {
            $table->id();
            $table->string('label');
        });
        DB::table('migration_viewer_guard')->insert(['label' => 'kept']);
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('migration_viewer_guard');

        parent::tearDown();
    }

    /**
     * @param  list<string>|null  $ran
     */
    private function inspector(?array $ran = ['2020_01_01_000000_create_shop_items_table']): MigrationInspector
    {
        return new MigrationInspector(
            sources: [new ApplicationMigrationSource(__DIR__.'/fixtures/migrations'), new FakeModuleMigrationSource],
            replayer: $this->app->make(MigrationReplayer::class),
            ran: fn (): ?array => $ran,
        );
    }

    public function test_files_are_listed_once_in_run_order_with_their_origin(): void
    {
        $files = $this->inspector()->files();

        $this->assertSame([
            '2020_01_01_000000_create_shop_items_table',
            '2020_01_01_100000_create_shop_categories_table',
            '2020_01_02_000000_alter_shop_items_table',
            '2020_01_03_000000_rename_shop_items_table',
            '2020_01_04_000000_fill_guard_table',
            '2020_01_05_000000_create_shop_logs_table',
            '2020_01_06_000000_drop_shop_logs_table',
        ], array_keys($files));
        $this->assertSame(Origin::Module, $files['2020_01_01_100000_create_shop_categories_table']->origin);
        $this->assertSame('Shop', $files['2020_01_01_100000_create_shop_categories_table']->module);
        $this->assertSame(Origin::Application, $files['2020_01_01_000000_create_shop_items_table']->origin);
        $this->assertSame(['Shop'], $this->inspector()->modules());
    }

    public function test_the_replay_never_reaches_the_database(): void
    {
        $this->inspector()->replays();

        $this->assertFalse(Schema::hasTable('shop_items'));
        $this->assertFalse(Schema::hasTable('shop_categories'));
        $this->assertSame(['kept'], DB::table('migration_viewer_guard')->pluck('label')->all());
    }

    public function test_a_created_table_lists_its_columns_and_indexes(): void
    {
        $change = $this->change('shop_items', '2020_01_01_000000_create_shop_items_table');

        $this->assertSame(Operation::Create, $change->operation);
        $this->assertSame([
            '+ id bigInteger, unsigned, auto-increment',
            '+ name string(100)',
            '+ price decimal(8, 2), default 0',
            "+ status enum('draft', 'live'), default 'draft', comment 'Publication'",
            '+ created_at timestamp, nullable',
            '+ updated_at timestamp, nullable',
            '+ shop_items_name_unique unique (name)',
        ], $this->lines($change));
    }

    public function test_an_altered_table_lists_renamed_dropped_and_added_items(): void
    {
        $change = $this->change('shop_items', '2020_01_02_000000_alter_shop_items_table');

        $this->assertSame(Operation::Alter, $change->operation);
        $this->assertContains('− shop_items_name_unique', $this->lines($change));
        $this->assertContains('~ name → title', $this->lines($change));
        $this->assertContains('− price', $this->lines($change));
        $this->assertContains('+ category_id bigInteger, unsigned, nullable', $this->lines($change));
        $this->assertContains('+ shop_items_category_id_foreign (category_id) → shop_categories(id), on delete set null', $this->lines($change));
    }

    public function test_tables_are_summarized_as_the_migrations_leave_them(): void
    {
        $tables = $this->inspector()->tables();

        $this->assertSame(['shop_categories', 'shop_items', 'shop_logs', 'shop_products'], array_keys($tables));

        // Renamed: the columns move to the new name, the old one counts as dropped.
        $this->assertTrue($tables['shop_items']->dropped);
        $this->assertSame(['id', 'title', 'status', 'created_at', 'updated_at', 'category_id'], array_keys($tables['shop_products']->columns));
        $this->assertCount(3, $tables['shop_items']->changes);

        $this->assertTrue($tables['shop_logs']->dropped);
        $this->assertFalse($tables['shop_categories']->dropped);
    }

    public function test_a_failing_migration_keeps_what_was_recorded_before(): void
    {
        $failures = $this->inspector()->failures();

        $this->assertCount(1, $failures);
        $this->assertSame('Broken on purpose', $failures[0]->error);
        $this->assertSame(['+ id bigInteger, unsigned, auto-increment', '+ message text'], $this->lines($failures[0]->changes[0]));
    }

    public function test_a_migration_without_schema_change_is_a_data_change_on_no_table(): void
    {
        $changes = $this->inspector()->changes(new MigrationQuery(operation: Operation::Data));

        $this->assertCount(1, $changes);
        $this->assertNull($changes[0]->table);
        $this->assertSame('2020_01_04_000000_fill_guard_table', $changes[0]->migration->name);
    }

    public function test_changes_are_grouped_by_table_and_can_be_filtered(): void
    {
        $inspector = $this->inspector();

        $tables = array_map(fn (TableChange $change): ?string => $change->table, $inspector->changes());
        $this->assertSame(['shop_categories', 'shop_items', 'shop_items', 'shop_items', 'shop_logs', 'shop_logs', null], $tables);

        $this->assertCount(1, $inspector->changes(new MigrationQuery(origin: Origin::Module)));
        $this->assertCount(1, $inspector->changes(new MigrationQuery(module: 'Shop')));
        $this->assertCount(1, $inspector->changes(new MigrationQuery(ran: true)));
        $this->assertCount(6, $inspector->changes(new MigrationQuery(ran: false)));
        $this->assertCount(2, $inspector->changes(new MigrationQuery(table: 'shop_logs')));
        // The search also looks at the column names.
        $this->assertSame(['shop_items'], array_map(fn (TableChange $change): ?string => $change->table, $inspector->changes(new MigrationQuery(search: 'CATEGORY_ID'))));
    }

    public function test_the_status_is_unknown_when_the_migrations_table_cannot_be_read(): void
    {
        $this->assertNull($this->inspector(ran: null)->changes()[0]->ran);
        $this->assertCount(0, $this->inspector(ran: null)->changes(new MigrationQuery(ran: true)));
    }

    public function test_a_change_detail_reads_as_one_line(): void
    {
        $this->assertSame('+ email string(255)', (string) new ChangeDetail(ChangeKind::AddColumn, 'email', 'string(255)'));
        $this->assertSame('− users_email_unique', (string) new ChangeDetail(ChangeKind::DropIndex, 'users_email_unique'));
    }

    private function change(string $table, string $migration): TableChange
    {
        foreach ($this->inspector()->changes(new MigrationQuery(table: $table)) as $change) {
            if ($change->migration->name === $migration) {
                return $change;
            }
        }

        $this->fail("No change of {$migration} on {$table}.");
    }

    /**
     * @return list<string>
     */
    private function lines(TableChange $change): array
    {
        return array_map(fn (ChangeDetail $detail): string => (string) $detail, $change->details);
    }
}
