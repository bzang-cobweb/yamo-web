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
    ) {}

    public function __toString(): string
    {
        return trim($this->kind->sign().' '.$this->subject.($this->definition !== '' ? ' '.$this->definition : ''));
    }
}
