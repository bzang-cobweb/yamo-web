<?php

namespace Bza\MigrationViewer\Console;

use Bza\MigrationViewer\MigrationTableSynchronizer;
use Illuminate\Console\Command;

class SyncMigrationTablesCommand extends Command
{
    protected $signature = 'migration-viewer:sync';

    protected $description = 'Replay the migrations and store each table they touch in migration_tables';

    public function handle(MigrationTableSynchronizer $synchronizer): int
    {
        $count = $synchronizer->sync();

        $this->components->info("{$count} tables synchronized.");

        return self::SUCCESS;
    }
}
