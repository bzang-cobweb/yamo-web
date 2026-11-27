<?php

namespace Bza\ConfigViewer\Data;

use Bza\ConfigViewer\Origin;

/**
 * A configuration file and the dotted key its content is loaded under.
 */
final readonly class ConfigFile
{
    public function __construct(
        /** The dotted key the file is loaded under, e.g. "partner" or "partner.logo". */
        public string $key,
        public string $path,
        public Origin $origin,
        /** The module name, for a module file. */
        public ?string $module = null,
    ) {}

    /** The top-level key: the group the file belongs to. */
    public function group(): string
    {
        return explode('.', $this->key, 2)[0];
    }

    /**
     * @return array{key: string, path: string, origin: string, module: ?string}
     */
    public function toArray(): array
    {
        return [
            'key' => $this->key,
            'path' => $this->path,
            'origin' => $this->origin->value,
            'module' => $this->module,
        ];
    }
}
