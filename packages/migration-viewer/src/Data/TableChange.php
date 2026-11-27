<?php

namespace Bza\MigrationViewer\Data;

use Bza\MigrationViewer\Operation;

/**
 * What one migration does to one table.
 */
final readonly class TableChange
{
    /**
     * @param  list<ChangeDetail>  $details
     */
    public function __construct(
        /** The table, null for a migration that touches no table. */
        public ?string $table,
        public MigrationFile $migration,
        public Operation $operation,
        public array $details = [],
        /** Whether the migration has run; null when the migrations table cannot be read. */
        public ?bool $ran = null,
        /** The new name, for a renamed table. */
        public ?string $renamedTo = null,
    ) {}

    /**
     * A key unique among all changes, for adapters that need one per row.
     */
    public function key(): string
    {
        return $this->migration->name.'#'.($this->table ?? '');
    }

    public function matches(string $search): bool
    {
        $haystack = [$this->table ?? '', $this->migration->name, $this->renamedTo ?? '', ...array_map(fn (ChangeDetail $detail): string => $detail->subject, $this->details)];

        foreach ($haystack as $text) {
            if (str_contains(mb_strtolower($text), mb_strtolower($search))) {
                return true;
            }
        }

        return false;
    }
}
