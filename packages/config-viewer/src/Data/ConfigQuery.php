<?php

namespace Bza\ConfigViewer\Data;

use Bza\ConfigViewer\Origin;
use Bza\ConfigViewer\Support\ValueType;

/**
 * Narrows the entries an adapter asks for. Every criterion is optional.
 */
final readonly class ConfigQuery
{
    /**
     * @param  list<ValueType>  $types
     */
    public function __construct(
        /** Only this top-level key. */
        public ?string $group = null,
        /** Only groups with this origin. */
        public ?Origin $origin = null,
        /** Only groups fed by this module (its name, e.g. "Partner"). */
        public ?string $module = null,
        /** Only entries whose value has one of these types; none means any type. */
        public array $types = [],
        /** Only entries whose key or (unmasked) value contains this text. */
        public ?string $search = null,
    ) {}
}
