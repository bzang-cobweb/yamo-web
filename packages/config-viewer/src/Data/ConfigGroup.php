<?php

namespace Bza\ConfigViewer\Data;

use Bza\ConfigViewer\Origin;

/**
 * A top-level configuration key ("app", "database", "partner"…) and the files
 * that feed it. A group can mix origins: a module named "Auth" merges its
 * config into the application's "auth" key.
 */
final readonly class ConfigGroup
{
    /**
     * @param  list<ConfigFile>  $files
     */
    public function __construct(
        public string $key,
        public array $files = [],
    ) {}

    /**
     * The origins of the group, application first. A group without file is a
     * package one.
     *
     * @return list<Origin>
     */
    public function origins(): array
    {
        if ($this->files === []) {
            return [Origin::Package];
        }

        $present = array_map(fn (ConfigFile $file): Origin => $file->origin, $this->files);

        return array_values(array_filter(Origin::cases(), fn (Origin $origin): bool => in_array($origin, $present, true)));
    }

    public function hasOrigin(Origin $origin): bool
    {
        return in_array($origin, $this->origins(), true);
    }

    /**
     * The modules contributing to the group.
     *
     * @return list<string>
     */
    public function modules(): array
    {
        $modules = array_map(fn (ConfigFile $file): ?string => $file->module, $this->files);

        return array_values(array_unique(array_filter($modules)));
    }

    /**
     * @return array{key: string, origins: list<string>, modules: list<string>, files: list<array<string, mixed>>}
     */
    public function toArray(): array
    {
        return [
            'key' => $this->key,
            'origins' => array_map(fn (Origin $origin): string => $origin->value, $this->origins()),
            'modules' => $this->modules(),
            'files' => array_map(fn (ConfigFile $file): array => $file->toArray(), $this->files),
        ];
    }
}
