<?php

namespace Bza\MigrationViewer;

/**
 * Where a migration file comes from.
 */
enum Origin: string
{
    /** A file in the application's migrations directory. */
    case Application = 'application';

    /** A file in a module's migrations directory. */
    case Module = 'module';

    /** A file in the generator's directory (written from the back-office). */
    case Generated = 'generated';

    /** A file registered by a package (loadMigrationsFrom). */
    case Package = 'package';

    public function label(): string
    {
        return __('migration-viewer::migration-viewer.origins.'.$this->value);
    }
}
