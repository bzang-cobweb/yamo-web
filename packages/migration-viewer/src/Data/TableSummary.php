<?php

namespace Bza\MigrationViewer\Data;

/**
 * A table as the migrations leave it, once all of them are replayed in order.
 */
final readonly class TableSummary
{
    /**
     * @param  array<string, string>  $columns  Each column's definition, keyed by its name, in creation order.
     * @param  list<TableChange>  $changes  The migrations touching the table, in run order.
     */
    public function __construct(
        public string $name,
        public array $columns,
        public array $changes,
        /** Dropped (or renamed away) by a later migration. */
        public bool $dropped = false,
    ) {}
}
