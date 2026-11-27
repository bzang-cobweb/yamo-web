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

    public function label(): string
    {
        return __('migration-viewer::migration-viewer.operations.'.$this->value);
    }
}
