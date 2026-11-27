<?php

namespace Bza\ConfigViewer\Data;

use Bza\ConfigViewer\Origin;

/**
 * Narrows the entries an adapter asks for. Every criterion is optional.
 */
final readonly class ConfigQuery
{
    public function __construct(
        /** Only this top-level key. */
        public ?string $group = null,
        /** Only groups with this origin. */
        public ?Origin $origin = null,
        /** Only entries whose key or (unmasked) value contains this text. */
        public ?string $search = null,
    ) {}
}
