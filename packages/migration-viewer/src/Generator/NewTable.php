<?php

namespace Bza\MigrationViewer\Generator;

use InvalidArgumentException;

/**
 * A table to generate.
 */
final readonly class NewTable
{
    /**
     * @param  list<NewColumn>  $columns
     */
    public function __construct(
        public string $name,
        public array $columns,
        /** An auto-increment "id" primary key first. */
        public bool $id = true,
        /** created_at and updated_at last. */
        public bool $timestamps = true,
        /** deleted_at, for soft deletes. */
        public bool $softDeletes = false,
    ) {
        if (preg_match(NewColumn::NAME_PATTERN, $name) !== 1) {
            throw new InvalidArgumentException("Invalid table name [{$name}].");
        }

        $names = array_map(fn (NewColumn $column): string => $column->name, $columns);

        if (count($names) !== count(array_unique($names))) {
            throw new InvalidArgumentException("The table [{$name}] has the same column twice.");
        }

        $reserved = array_intersect($names, $this->implicitColumns());

        if ($reserved !== []) {
            throw new InvalidArgumentException('Already added by the table options: '.implode(', ', $reserved).'.');
        }
    }

    /**
     * The columns the options add.
     *
     * @return list<string>
     */
    public function implicitColumns(): array
    {
        return array_values(array_filter([
            $this->id ? 'id' : null,
            $this->timestamps ? 'created_at' : null,
            $this->timestamps ? 'updated_at' : null,
            $this->softDeletes ? 'deleted_at' : null,
        ]));
    }
}
