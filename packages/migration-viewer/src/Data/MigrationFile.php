<?php

namespace Bza\MigrationViewer\Data;

use Bza\MigrationViewer\Origin;

/**
 * A migration file, named as in the migrations table ("2026_01_01_000000_create_users_table").
 */
final readonly class MigrationFile
{
    public function __construct(
        public string $name,
        public string $path,
        public Origin $origin,
        /** The module's name ("Partner") when the origin is Module. */
        public ?string $module = null,
    ) {}
}
