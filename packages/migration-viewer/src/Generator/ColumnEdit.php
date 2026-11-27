<?php

namespace Bza\MigrationViewer\Generator;

/**
 * One item of the edit form: an existing column (by its original name), kept,
 * renamed or changed, or a new one.
 */
final readonly class ColumnEdit
{
    public function __construct(
        /** The column's current name; null for a new column. */
        public ?string $original,
        /** The wanted column; null keeps a locked column as it is. */
        public ?NewColumn $spec,
    ) {}

    /**
     * From an edit form item: NewColumn::fromArray() fields plus "original" and "locked".
     *
     * @param  array<string, mixed>  $item
     */
    public static function fromArray(array $item): self
    {
        $original = isset($item['original']) && $item['original'] !== '' ? (string) $item['original'] : null;

        return new self($original, ! empty($item['locked']) ? null : NewColumn::fromArray($item));
    }
}
