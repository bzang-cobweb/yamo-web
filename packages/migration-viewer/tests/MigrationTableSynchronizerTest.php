<?php

namespace Bza\MigrationViewer\Tests;

use Bza\MigrationViewer\MigrationInspector;
use Bza\MigrationViewer\MigrationTableSynchronizer;
use Bza\MigrationViewer\Models\MigrationTable;
use Bza\MigrationViewer\Replay\MigrationReplayer;
use Bza\MigrationViewer\Sources\ApplicationMigrationSource;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase;

/**
 * Stores the fixture migrations' tables in migration_tables (the package's own
 * migration, run by the host application).
 */
class MigrationTableSynchronizerTest extends TestCase
{
    use RefreshDatabase;

    private function synchronizer(): MigrationTableSynchronizer
    {
        return new MigrationTableSynchronizer(new MigrationInspector(
            sources: [new ApplicationMigrationSource(__DIR__.'/fixtures/migrations'), new FakeModuleMigrationSource],
            replayer: $this->app->make(MigrationReplayer::class),
            ran: fn (): array => ['2020_01_01_000000_create_shop_items_table'],
        ));
    }

    public function test_each_table_is_stored_once_with_its_files_and_columns(): void
    {
        MigrationTable::query()->delete();

        $this->assertSame(4, $this->synchronizer()->sync());
        $this->assertSame(['shop_categories', 'shop_items', 'shop_logs', 'shop_products'], MigrationTable::query()->orderBy('name')->pluck('name')->all());

        $items = MigrationTable::query()->where('name', 'shop_items')->firstOrFail();
        $this->assertTrue($items->dropped);
        $this->assertSame(3, $items->migrations_count);
        $this->assertSame(2, $items->pending_count);
        $this->assertSame('2020_01_03_000000_rename_shop_items_table', $items->last_migration);
        $this->assertSame(['migration' => '2020_01_01_000000_create_shop_items_table', 'operation' => 'create', 'origin' => 'application', 'status' => 'ran'], array_intersect_key($items->migrations[0], array_flip(['migration', 'operation', 'origin', 'status'])));
        $this->assertContains('+ shop_items_name_unique unique (name)', $items->migrations[0]['details']);

        $products = MigrationTable::query()->where('name', 'shop_products')->firstOrFail();
        $this->assertFalse($products->dropped);
        $this->assertSame(['name' => 'title', 'definition' => 'string(100)'], $products->columns[1]);
        $this->assertSame(6, $products->columns_count);

        $categories = MigrationTable::query()->where('name', 'shop_categories')->firstOrFail();
        $this->assertSame(['origin' => 'module', 'module' => 'Shop'], array_intersect_key($categories->migrations[0], array_flip(['origin', 'module'])));
    }

    public function test_a_new_sync_updates_rows_and_removes_stale_tables(): void
    {
        MigrationTable::query()->delete();
        MigrationTable::query()->create(['name' => 'shop_items', 'columns' => [], 'migrations' => [], 'columns_count' => 99]);
        MigrationTable::query()->create(['name' => 'stale', 'columns' => [], 'migrations' => []]);

        $this->synchronizer()->sync();
        $this->synchronizer()->sync();

        $this->assertSame(4, MigrationTable::query()->count());
        $this->assertSame(0, MigrationTable::query()->where('name', 'shop_items')->value('columns_count'));
        $this->assertNotNull(MigrationTable::query()->where('name', 'shop_items')->value('synced_at'));
    }
}
