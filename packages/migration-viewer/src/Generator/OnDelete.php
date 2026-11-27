<?php

namespace Bza\MigrationViewer\Generator;

/**
 * What a foreign key does when the referenced row is deleted.
 */
enum OnDelete: string
{
    case Cascade = 'cascade';
    case SetNull = 'set null';
    case Restrict = 'restrict';

    /** The ForeignKeyDefinition method. */
    public function method(): string
    {
        return match ($this) {
            self::Cascade => 'cascadeOnDelete',
            self::SetNull => 'nullOnDelete',
            self::Restrict => 'restrictOnDelete',
        };
    }
}
