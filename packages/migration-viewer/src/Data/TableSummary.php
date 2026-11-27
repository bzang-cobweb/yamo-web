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
     * @param  array<string, array<string, mixed>>  $columnAttributes  Each column's attributes (see ChangeDetail::$attributes), keyed by its name.
     * @param  array<string, array{type: string, columns: list<string>}>  $indexes  Keyed by the index name.
     * @param  array<string, array{columns: list<string>, on: string, references: list<string>, onDelete: ?string}>  $foreignKeys  Keyed by the constraint name.
     */
    public function __construct(
        public string $name,
        public array $columns,
        public array $changes,
        /** Dropped (or renamed away) by a later migration. */
        public bool $dropped = false,
        public array $columnAttributes = [],
        public array $indexes = [],
        public array $foreignKeys = [],
    ) {}
}
