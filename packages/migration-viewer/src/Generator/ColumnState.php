<?php

namespace Bza\MigrationViewer\Generator;

/**
 * A column of an existing table, as the edit form starts from it.
 */
final readonly class ColumnState
{
    public function __construct(
        public string $name,
        /** The column as the generator would write it; null when it cannot (see $locked). */
        public ?NewColumn $spec,
        /** The definition shown to the user ("string(255), nullable"). */
        public string $definition,
        /** The single-column unique index on it, by name. */
        public ?string $uniqueIndex = null,
        /** The single-column plain index on it, by name. */
        public ?string $plainIndex = null,
        /** The foreign key on it, by name. */
        public ?string $foreignKey = null,
    ) {}

    /**
     * Columns the generator cannot rewrite (an auto-increment key, a computed
     * column, an expression default, a type it does not offer…) can only be
     * kept or dropped.
     */
    public function locked(): bool
    {
        return $this->spec === null;
    }

    /**
     * The edit form's item for this column.
     *
     * @return array<string, mixed>
     */
    public function toFormItem(): array
    {
        $spec = $this->spec;

        return [
            'original' => $this->name,
            'locked' => $spec === null,
            'definition' => $this->definition,
            'name' => $this->name,
            'type' => $spec?->type->value,
            'length' => $spec?->length,
            'total' => $spec?->total,
            'places' => $spec?->places,
            'nullable' => $spec?->nullable ?? false,
            'default' => $spec?->default,
            'unique' => $spec?->unique ?? false,
            'index' => $spec?->index ?? false,
            'references' => $spec?->references,
            'on_delete' => $spec?->onDelete?->value,
        ];
    }
}
