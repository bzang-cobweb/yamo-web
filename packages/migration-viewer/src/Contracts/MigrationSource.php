<?php

namespace Bza\MigrationViewer\Contracts;

use Bza\MigrationViewer\Data\MigrationFile;

/**
 * Lists migration files and tells where each comes from.
 */
interface MigrationSource
{
    /**
     * @return iterable<MigrationFile>
     */
    public function files(): iterable;
}
