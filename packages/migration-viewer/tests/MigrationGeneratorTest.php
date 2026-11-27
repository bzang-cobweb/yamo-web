<?php

namespace Bza\MigrationViewer\Tests;

use Bza\MigrationViewer\Data\ChangeDetail;
use Bza\MigrationViewer\Generator\ColumnType;
use Bza\MigrationViewer\Generator\MigrationGenerator;
use Bza\MigrationViewer\Generator\NewColumn;
use Bza\MigrationViewer\Generator\NewTable;
use Bza\MigrationViewer\Generator\OnDelete;
use Bza\MigrationViewer\MigrationInspector;
use Bza\MigrationViewer\Origin;
use Bza\MigrationViewer\Replay\MigrationReplayer;
use Bza\MigrationViewer\Sources\ApplicationMigrationSource;
use Bza\MigrationViewer\Sources\GeneratedMigrationSource;
use DateTimeImmutable;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\TestCase;
use InvalidArgumentException;

/**
 * Generates migration files in a temporary directory, next to the fixture
 * migrations, then replays them: what is written is what the viewer reads.
 */
class MigrationGeneratorTest extends TestCase
{
    private string $directory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->directory = storage_path('framework/testing/migration-generator-'.uniqid());
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

    private function inspector(): MigrationInspector
    {
        return new MigrationInspector(
            sources: [new ApplicationMigrationSource(__DIR__.'/fixtures/migrations'), new FakeModuleMigrationSource, new GeneratedMigrationSource],
            replayer: $this->app->make(MigrationReplayer::class),
        );
    }

    private function generator(string $now = '2030-01-02 03:04:05'): MigrationGenerator
    {
        return new MigrationGenerator($this->directory, $this->inspector(), clock: fn (): DateTimeImmutable => new DateTimeImmutable($now));
    }

    private function table(): NewTable
    {
        return new NewTable('shop_orders', [
            new NewColumn('reference', ColumnType::String, length: 40, unique: true),
            new NewColumn('total', ColumnType::Decimal, total: 10, places: 2, default: '0'),
            new NewColumn('paid', ColumnType::Boolean, default: 'oui'),
            new NewColumn('note', ColumnType::Text, nullable: true),
            new NewColumn('category_id', ColumnType::ForeignId, nullable: true, references: 'shop_categories', onDelete: OnDelete::SetNull),
            new NewColumn('status', ColumnType::String, default: "it's new", index: true),
        ], softDeletes: true);
    }

    public function test_a_new_table_is_written_as_a_create_migration(): void
    {
        $path = $this->generator()->createTable($this->table());

        $this->assertSame($this->directory.'/2030_01_02_030405_create_shop_orders_table.php', $path);
        $this->assertSame(<<<'PHP'
            <?php

            use Illuminate\Database\Migrations\Migration;
            use Illuminate\Database\Schema\Blueprint;
            use Illuminate\Support\Facades\Schema;

            return new class extends Migration
            {
                public function up(): void
                {
                    Schema::create('shop_orders', function (Blueprint $table) {
                        $table->id();
                        $table->string('reference', 40)->unique();
                        $table->decimal('total', 10, 2)->default(0);
                        $table->boolean('paid')->default(true);
                        $table->text('note')->nullable();
                        $table->foreignId('category_id')->nullable()->constrained('shop_categories')->nullOnDelete();
                        $table->string('status')->default('it\'s new')->index();
                        $table->timestamps();
                        $table->softDeletes();
                    });
                }

                public function down(): void
                {
                    Schema::dropIfExists('shop_orders');
                }
            };

            PHP, file_get_contents($path));
    }

    public function test_the_viewer_reads_the_generated_file_as_a_pending_generated_migration(): void
    {
        $this->generator()->createTable($this->table());

        $inspector = $this->inspector();
        $files = $inspector->files();

        $this->assertSame(Origin::Generated, $files['2030_01_02_030405_create_shop_orders_table']->origin);
        $this->assertSame(
            ['id', 'reference', 'total', 'paid', 'note', 'category_id', 'status', 'created_at', 'updated_at', 'deleted_at'],
            array_keys($inspector->table('shop_orders')->columns),
        );
        $this->assertContains('+ shop_orders_category_id_foreign (category_id) → shop_categories(id), on delete set null', array_map(
            fn (ChangeDetail $detail): string => (string) $detail,
            $inspector->table('shop_orders')->changes[0]->details,
        ));
    }

    public function test_columns_are_added_to_an_existing_table(): void
    {
        $path = $this->generator()->addColumns('shop_products', [
            new NewColumn('sku', ColumnType::Char, length: 12, nullable: true),
            new NewColumn('category_ref_id', ColumnType::ForeignId, nullable: true, references: 'shop_categories'),
        ]);

        $this->assertStringEndsWith('2030_01_02_030405_add_sku_and_category_ref_id_to_shop_products_table.php', $path);
        $code = file_get_contents($path);
        $this->assertStringContainsString("Schema::table('shop_products', function (Blueprint \$table) {\n            \$table->char('sku', 12)->nullable();", $code);
        $this->assertStringContainsString("\$table->dropConstrainedForeignId('category_ref_id');\n            \$table->dropColumn('sku');", $code);

        $this->assertSame(['sku', 'category_ref_id'], array_slice(array_keys($this->inspector()->table('shop_products')->columns), -2));
    }

    public function test_a_new_file_always_runs_after_the_existing_ones(): void
    {
        $first = $this->generator()->addColumns('shop_products', [new NewColumn('a', ColumnType::Integer)]);
        // Same second: one second after the file just written.
        $second = $this->generator()->addColumns('shop_products', [new NewColumn('b', ColumnType::Integer)]);
        $this->assertStringContainsString('2030_01_02_030405_add_a_', $first);
        $this->assertStringContainsString('2030_01_02_030406_add_b_', $second);

        // A clock behind the last migration (2020_01_06_000000 among the fixtures, once the two files above are gone).
        array_map('unlink', [$first, $second]);
        $late = $this->generator('2019-05-05 10:00:00')->addColumns('shop_products', [new NewColumn('c', ColumnType::Integer)]);
        $this->assertStringContainsString('2020_01_06_000001_add_c_', $late);
    }

    public function test_two_files_of_the_same_name_and_second_do_not_overwrite_each_other(): void
    {
        // Two generators that both replayed before either wrote.
        $one = $this->generator();
        $other = $this->generator();
        $one->tableExists('shop_categories');
        $other->tableExists('shop_categories');

        $first = $one->addColumns('shop_categories', [new NewColumn('c', ColumnType::Integer)]);
        $second = $other->addColumns('shop_categories', [new NewColumn('c', ColumnType::Integer)]);

        $this->assertStringContainsString('2030_01_02_030405_add_c_', $first);
        $this->assertStringContainsString('2030_01_02_030406_add_c_', $second);
    }

    public function test_an_existing_table_is_not_created_again(): void
    {
        $this->expectExceptionMessage('The table [shop_categories] already exists.');

        $this->generator()->createTable(new NewTable('shop_categories', []));
    }

    public function test_a_dropped_table_can_be_created_again(): void
    {
        $this->assertFileExists($this->generator()->createTable(new NewTable('shop_logs', [])));
    }

    public function test_columns_are_refused_on_a_missing_table_or_when_they_exist(): void
    {
        $generator = $this->generator();

        $this->assertRefused(fn () => $generator->addColumns('shop_items', [new NewColumn('x', ColumnType::Integer)]), 'does not exist');
        $this->assertRefused(fn () => $generator->addColumns('shop_products', [new NewColumn('title', ColumnType::Integer)]), 'already has: title');
        $this->assertRefused(fn () => $generator->addColumns('shop_products', []), 'No column');
        $this->assertSame([], glob($this->directory.'/*.php') ?: []);
    }

    public function test_invalid_specs_are_refused(): void
    {
        $this->assertRefused(fn () => new NewColumn('Bad-Name', ColumnType::String), 'Invalid column name');
        $this->assertRefused(fn () => new NewTable('1table', []), 'Invalid table name');
        $this->assertRefused(fn () => new NewTable('t', [new NewColumn('a', ColumnType::String), new NewColumn('a', ColumnType::Text)]), 'twice');
        $this->assertRefused(fn () => new NewTable('t', [new NewColumn('created_at', ColumnType::Timestamp)]), 'created_at');
        $this->assertRefused(fn () => new NewColumn('user_id', ColumnType::ForeignId, onDelete: OnDelete::SetNull), 'nullable');
    }

    public function test_every_type_is_a_blueprint_method(): void
    {
        foreach (ColumnType::cases() as $type) {
            $this->assertTrue(method_exists(Blueprint::class, $type->value), "Blueprint has no {$type->value}().");
        }
    }

    public function test_a_column_is_read_from_a_form_state(): void
    {
        $column = NewColumn::fromArray(['name' => 'price', 'type' => 'decimal', 'total' => '6', 'places' => '', 'default' => '', 'nullable' => true, 'on_delete' => null]);

        $this->assertSame(6, $column->total);
        $this->assertNull($column->places);
        $this->assertFalse($column->hasDefault());
        $this->assertNull($column->onDelete);
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
