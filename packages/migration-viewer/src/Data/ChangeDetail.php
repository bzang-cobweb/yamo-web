<?php

namespace Bza\MigrationViewer\Data;

use Bza\MigrationViewer\ChangeKind;

/**
 * One change on a table: a column, an index, a foreign key…
 */
final readonly class ChangeDetail
{
    public function __construct(
        public ChangeKind $kind,
        /** The column, index or command name. */
        public string $subject,
        /** A short technical description ("string(100), nullable"), empty when there is none. */
        public string $definition = '',
        /** The new name, for a renamed column. */
        public ?string $target = null,
        /**
         * Structured facts, for tools that rebuild the schema: a column's
         * attributes (type, length, nullable, default…), an index's type and
         * columns, a foreign key's columns, table and on-delete rule.
         *
         * @var array<string, mixed>
         */
        public array $attributes = [],
    ) {}

    public function __toString(): string
    {
        return trim($this->kind->sign().' '.$this->subject.($this->definition !== '' ? ' '.$this->definition : ''));
    }
}
