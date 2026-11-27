<?php

namespace Bza\MigrationViewer\Tests;

use Bza\MigrationViewer\Generator\ColumnEdit;
use Bza\MigrationViewer\Generator\ColumnType;
use Bza\MigrationViewer\Generator\MigrationGenerator;
use Bza\MigrationViewer\Generator\NewColumn;
use Bza\MigrationViewer\Generator\OnDelete;
use Bza\MigrationViewer\MigrationInspector;
use Bza\MigrationViewer\Replay\MigrationReplayer;
use Bza\MigrationViewer\Sources\ApplicationMigrationSource;
use Bza\MigrationViewer\Sources\GeneratedMigrationSource;
use DateTimeImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase;
use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;

/**
 * The edit of an existing table: its current columns read back, the wanted
 * ones compared with them, one migration written, then really run up and down.
 */
class TableAlterationTest extends TestCase
{
    use RefreshDatabase;

    private string $directory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->directory = storage_path('framework/testing/table-alteration-'.uniqid());
        config(['migration-viewer.generator.path' => $this->directory]);
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

    private function generator(): MigrationGenerator
    {
        return new MigrationGenerator($this->directory, new MigrationInspector(
            sources: [new ApplicationMigrationSource(__DIR__.'/fixtures/migrations'), new FakeModuleMigrationSource, new GeneratedMigrationSource],
            replayer: $this->app->make(MigrationReplayer::class),
        ), clock: fn (): DateTimeImmutable => new DateTimeImmutable('2030-01-01 00:00:00'));
    }

    /**
     * Builds shop_categories and shop_products for real, as the fixtures do.
     */
    private function migrateFixtures(): void
    {
        foreach ([
            __DIR__.'/fixtures/Modules/Shop/database/migrations/2020_01_01_100000_create_shop_categories_table.php',
            __DIR__.'/fixtures/migrations/2020_01_01_000000_create_shop_items_table.php',
            __DIR__.'/fixtures/migrations/2020_01_02_000000_alter_shop_items_table.php',
            __DIR__.'/fixtures/migrations/2020_01_03_000000_rename_shop_items_table.php',
        ] as $path) {
            (require $path)->up();
        }
    }

    public function test_the_current_columns_are_read_back_as_specs(): void
    {
        $columns = $this->generator()->currentColumns('shop_products');

        $this->assertSame(['id', 'title', 'status', 'created_at', 'updated_at', 'category_id'], array_keys($columns));
        // An auto-increment key and an enum are not offered by the generator: kept or dropped only.
        $this->assertTrue($columns['id']->locked());
        $this->assertTrue($columns['status']->locked());

        $this->assertEquals(new NewColumn('title', ColumnType::String, length: 100), $columns['title']->spec);
        $this->assertEquals(new NewColumn('category_id', ColumnType::ForeignId, nullable: true, references: 'shop_categories', onDelete: OnDelete::SetNull), $columns['category_id']->spec);
        // The key keeps the name it got when the table was still shop_items.
        $this->assertSame('shop_items_category_id_foreign', $columns['category_id']->foreignKey);
        $this->assertSame(['original' => 'title', 'locked' => false, 'name' => 'title', 'type' => 'string', 'length' => 100], array_intersect_key($columns['title']->toFormItem(), array_flip(['original', 'locked', 'name', 'type', 'length'])));
    }

    public function test_one_migration_holds_every_change_and_runs_up_and_down(): void
    {
        $this->migrateFixtures();
        $generator = $this->generator();

        $path = $generator->alterTable('shop_products', 'shop_articles', [
            new ColumnEdit('id', null),
            // Renamed, longer, nullable, unique.
            new ColumnEdit('title', new NewColumn('label', ColumnType::String, length: 150, nullable: true, unique: true)),
            new ColumnEdit('created_at', new NewColumn('created_at', ColumnType::Timestamp, nullable: true)),
            new ColumnEdit('updated_at', new NewColumn('updated_at', ColumnType::Timestamp, nullable: true)),
            // status and category_id are left out: dropped.
            new ColumnEdit(null, new NewColumn('stock', ColumnType::Integer, default: '0', index: true)),
        ]);

        $this->assertStringEndsWith('2030_01_01_000000_alter_shop_products_table.php', $path);
        $code = file_get_contents($path);
        $this->assertStringContainsString("Schema::rename('shop_products', 'shop_articles');", $code);
        $this->assertStringContainsString("\$table->dropForeign(['category_id'])->index('shop_items_category_id_foreign');", $code);
        $this->assertStringContainsString("\$table->dropColumn(['status', 'category_id']);", $code);
        $this->assertStringContainsString("\$table->renameColumn('title', 'label');", $code);
        $this->assertStringContainsString("\$table->string('label', 150)->nullable()->change();", $code);
        $this->assertStringContainsString("\$table->unique('label');", $code);
        $this->assertStringContainsString("\$table->integer('stock')->default(0)->index();", $code);
        $this->assertStringContainsString('// status (enum', $code);

        $migration = require $path;
        $migration->up();

        $this->assertFalse(Schema::hasTable('shop_products'));
        $this->assertSame(['id', 'label', 'created_at', 'updated_at', 'stock'], Schema::getColumnListing('shop_articles'));
        $this->assertTrue(collect(Schema::getColumns('shop_articles'))->firstWhere('name', 'label')['nullable']);
        $this->assertContains('shop_articles_label_unique', array_column(Schema::getIndexes('shop_articles'), 'name'));

        $migration->down();

        $this->assertFalse(Schema::hasTable('shop_articles'));
        // status cannot be restored by the generator (an enum); the rest is back.
        $this->assertSame(['id', 'title', 'created_at', 'updated_at', 'category_id'], Schema::getColumnListing('shop_products'));
        $this->assertSame(['category_id'], array_merge(...array_column(Schema::getForeignKeys('shop_products'), 'columns')));
        $this->assertFalse(collect(Schema::getColumns('shop_products'))->firstWhere('name', 'title')['nullable']);
    }

    public function test_the_viewer_reads_the_edit_back(): void
    {
        $generator = $this->generator();
        $generator->alterTable('shop_categories', 'shop_categories', [
            new ColumnEdit('id', null),
            new ColumnEdit('slug', new NewColumn('code', ColumnType::String, length: 50)),
            new ColumnEdit(null, new NewColumn('position', ColumnType::UnsignedInteger, default: '0')),
        ]);

        $columns = $this->generator()->currentColumns('shop_categories');

        $this->assertSame(['id', 'code', 'position'], array_keys($columns));
        $this->assertEquals(new NewColumn('code', ColumnType::String, length: 50), $columns['code']->spec);
        $this->assertNull($columns['code']->uniqueIndex);
    }

    public function test_an_edit_without_change_or_with_clashing_names_is_refused(): void
    {
        $generator = $this->generator();
        $unchanged = fn (): array => array_map(fn ($state) => new ColumnEdit($state->name, $state->spec), array_values($generator->currentColumns('shop_categories')));

        $this->assertRefused(fn () => $generator->plan('shop_categories', 'shop_categories', $unchanged()), 'Nothing changes');
        $this->assertRefused(fn () => $generator->plan('shop_categories', 'shop_products', $unchanged()), 'already exists');
        $this->assertRefused(fn () => $generator->plan('shop_categories', 'shop_categories', [...$unchanged(), new ColumnEdit(null, new NewColumn('slug', ColumnType::Text))]), 'Several columns are named slug');
        $this->assertRefused(fn () => $generator->plan('shop_categories', 'shop_categories', [new ColumnEdit('id', new NewColumn('id', ColumnType::Integer))]), 'only be kept or dropped');
        $this->assertRefused(fn () => $generator->plan('shop_categories', 'shop_categories', [new ColumnEdit('nope', null)]), 'no column [nope]');
        $this->assertRefused(fn () => $generator->plan('shop_items', 'shop_items', []), 'does not exist');
    }

    private function assertRefused(callable $callback, string $message): void
    {
        try {
            $callback();
        } catch (InvalidArgumentException $exception) {
            $this->assertStringContainsString($message, $exception->getMessage());

            return;
        }

        $this->fail("Not refused: {$message}");
    }
}
