<?php

namespace Bza\MigrationViewer\Data;

use Bza\MigrationViewer\Operation;
use Bza\MigrationViewer\Origin;

/**
 * Narrows the changes an adapter asks for. Every criterion is optional.
 */
final readonly class MigrationQuery
{
    public function __construct(
        /** Only this table. */
        public ?string $table = null,
        /** Only migrations with this origin. */
        public ?Origin $origin = null,
        /** Only migrations of this module (its name, e.g. "Partner"). */
        public ?string $module = null,
        /** Only this operation. */
        public ?Operation $operation = null,
        /** Only migrations that have run (true) or are pending (false). */
        public ?bool $ran = null,
        /** Only changes whose table, migration or column/index names contain this text. */
        public ?string $search = null,
    ) {}
}
