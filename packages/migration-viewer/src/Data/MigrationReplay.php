<?php

namespace Bza\MigrationViewer\Data;

/**
 * The outcome of replaying one migration's up() method.
 */
final readonly class MigrationReplay
{
    /**
     * @param  list<TableChange>  $changes
     */
    public function __construct(
        public MigrationFile $migration,
        public array $changes,
        /** The message of the exception thrown while replaying, if any. The changes recorded before it are kept. */
        public ?string $error = null,
    ) {}
}
