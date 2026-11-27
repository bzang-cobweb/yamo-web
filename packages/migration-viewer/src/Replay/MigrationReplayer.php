<?php

namespace Bza\MigrationViewer\Replay;

use Bza\MigrationViewer\Data\MigrationFile;
use Bza\MigrationViewer\Data\MigrationReplay;
use Bza\MigrationViewer\Data\TableChange;
use Bza\MigrationViewer\Operation;
use Closure;
use Illuminate\Database\Connection;
use Illuminate\Database\ConnectionResolverInterface;
use Illuminate\Database\Migrations\Migrator;
use Throwable;

/**
 * Replays a migration's up() method with every connection in "pretend" mode,
 * like "migrate --pretend": queries are recorded, never sent to the database.
 * The schema blueprints created meanwhile tell what the migration does.
 *
 * Limits: a check such as Schema::hasTable() sees an empty database, and code
 * that does more than query the database (files, cache, events) still runs.
 */
class MigrationReplayer
{
    /**
     * @param  list<string>  $connections  The connections to put in pretend mode.
     */
    public function __construct(
        private readonly Migrator $migrator,
        private readonly ConnectionResolverInterface $db,
        private readonly BlueprintCollector $collector,
        private readonly BlueprintReader $reader,
        private readonly array $connections,
    ) {}

    public function replay(MigrationFile $file, ?bool $ran = null): MigrationReplay
    {
        $connection = null;

        [$blueprints, $error] = $this->collector->record(function () use ($file, &$connection): void {
            $migration = $this->resolve($file->path);
            $connection = $this->migrator->resolveConnection(method_exists($migration, 'getConnection') ? $migration->getConnection() : null);

            $this->pretending($this->connections, function () use ($migration, $connection): void {
                if (method_exists($migration, 'up')) {
                    $this->run($connection, $migration);
                }
            });
        });

        $connection ??= $this->db->connection();
        $changes = $this->reader->read($blueprints, $file, $connection->getQueryGrammar(), $ran);

        if ($changes === []) {
            $changes = [new TableChange(null, $file, Operation::Data, ran: $ran)];
        }

        return new MigrationReplay($file, $changes, $error?->getMessage());
    }

    /**
     * Migrator::resolvePath() is protected: it is called in the migrator's scope
     * so that a migration file is loaded exactly as "migrate" loads it (anonymous
     * classes required once, named classes instantiated).
     */
    private function resolve(string $path): object
    {
        $migrator = $this->migrator;

        return (function () use ($path): object {
            return $this->resolvePath($path);
        })->call($migrator);
    }

    /**
     * Runs up() as Migrator::runMethod() does, with the migration's connection
     * as the default one.
     */
    private function run(Connection $connection, object $migration): void
    {
        $migrator = $this->migrator;

        (function () use ($connection, $migration): void {
            $this->runMethod($connection, $migration, 'up');
        })->call($migrator);
    }

    /**
     * @param  list<string>  $names
     */
    private function pretending(array $names, Closure $callback): void
    {
        if ($names === []) {
            $callback();

            return;
        }

        $name = array_shift($names);

        try {
            $connection = $this->db->connection($name);
        } catch (Throwable) {
            // A connection that cannot even be built cannot be written to.
            $this->pretending($names, $callback);

            return;
        }

        $connection->pretend(fn () => $this->pretending($names, $callback));
    }
}
