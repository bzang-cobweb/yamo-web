<?php

namespace Bza\MigrationViewer;

/**
 * What a migration does to a table.
 */
enum Operation: string
{
    case Create = 'create';

    case Alter = 'alter';

    case Rename = 'rename';

    case Drop = 'drop';

    /** No schema change: the migration only reads or writes rows. */
    case Data = 'data';

    /**
     * The Filament-style colour name adapters may use for this operation.
     */
    public function color(): string
    {
        return match ($this) {
            self::Create => 'success',
            self::Alter => 'info',
            self::Rename => 'warning',
            self::Drop => 'danger',
            self::Data => 'gray',
        };
    }

    public function label(): string
    {
        return __('migration-viewer::migration-viewer.operations.'.$this->value);
    }
}
