<?php

namespace Bza\MigrationViewer\Generator;

/**
 * Everything one edit changes on a table, written as a single migration.
 */
final readonly class TableAlteration
{
    /**
     * @param  list<ColumnState>  $drops
     * @param  array<string, string>  $renames  New name by original name.
     * @param  list<array{from: ColumnState, to: NewColumn}>  $changes  Definitions that change; "to" has the final name.
     * @param  list<NewColumn>  $adds
     */
    public function __construct(
        public string $table,
        public string $newName,
        public array $drops = [],
        public array $renames = [],
        public array $changes = [],
        public array $adds = [],
    ) {}

    public function renamesTable(): bool
    {
        return $this->newName !== $this->table;
    }

    public function isEmpty(): bool
    {
        return ! $this->renamesTable() && $this->drops === [] && $this->renames === [] && $this->changes === [] && $this->adds === [];
    }
}
