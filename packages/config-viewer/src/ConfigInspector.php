<?php

namespace Bza\ConfigViewer;

use Bza\ConfigViewer\Contracts\ConfigSource;
use Bza\ConfigViewer\Contracts\ValueMasker;
use Bza\ConfigViewer\Data\ConfigEntry;
use Bza\ConfigViewer\Data\ConfigFile;
use Bza\ConfigViewer\Data\ConfigGroup;
use Bza\ConfigViewer\Data\ConfigQuery;
use Bza\ConfigViewer\Support\ConfigFlattener;
use Bza\ConfigViewer\Support\ValueFormatter;
use Bza\ConfigViewer\Support\ValueType;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Support\Str;

/**
 * The single entry point of the core: reads the effective configuration, groups
 * it by top-level key, attributes each group to its files and masks sensitive
 * values. Adapters (Filament page, API, console…) only talk to this class and
 * only ever receive masked data.
 */
class ConfigInspector
{
    /** @var array<string, ConfigGroup>|null */
    private ?array $groups = null;

    /**
     * @param  iterable<ConfigSource>  $sources
     * @param  list<string>  $hidden  Patterns of keys never listed.
     */
    public function __construct(
        private readonly Repository $config,
        private readonly iterable $sources,
        private readonly ValueMasker $masker,
        private readonly ConfigFlattener $flattener = new ConfigFlattener,
        private readonly ValueFormatter $formatter = new ValueFormatter,
        private readonly array $hidden = [],
    ) {}

    /**
     * Every visible group, sorted by key. Keys present in the repository but
     * claimed by no source are package groups.
     *
     * @return array<string, ConfigGroup>
     */
    public function groups(): array
    {
        return $this->groups ??= $this->buildGroups();
    }

    public function group(string $key): ?ConfigGroup
    {
        return $this->groups()[$key] ?? null;
    }

    /**
     * The modules contributing to at least one visible group, sorted by name.
     *
     * @return list<string>
     */
    public function modules(): array
    {
        $modules = array_merge([], ...array_map(fn (ConfigGroup $group): array => $group->modules(), array_values($this->groups())));
        $modules = array_values(array_unique($modules));
        sort($modules, SORT_NATURAL | SORT_FLAG_CASE);

        return $modules;
    }

    /**
     * The entries matching the query, in key order.
     *
     * @return list<ConfigEntry>
     */
    public function entries(ConfigQuery $query = new ConfigQuery): array
    {
        $entries = [];

        foreach ($this->groups() as $group) {
            if (! $this->groupMatches($group, $query)) {
                continue;
            }

            foreach ($this->flattener->flatten($this->config->get($group->key), $group->key) as $key => $value) {
                if ($this->isHidden($key)) {
                    continue;
                }

                $entry = $this->makeEntry($key, $group->key, $value);

                if ($query->search !== null && $query->search !== '' && ! $entry->matches($query->search)) {
                    continue;
                }

                $entries[] = $entry;
            }
        }

        return $entries;
    }

    /**
     * One entry by its full dotted key, or null if missing or hidden.
     */
    public function entry(string $key): ?ConfigEntry
    {
        $group = explode('.', $key, 2)[0];

        if ($this->group($group) === null || $this->isHidden($key) || ! $this->config->has($key)) {
            return null;
        }

        return $this->makeEntry($key, $group, $this->config->get($key));
    }

    /**
     * @return array<string, ConfigGroup>
     */
    private function buildGroups(): array
    {
        /** @var array<string, list<ConfigFile>> $files */
        $files = [];

        foreach ($this->sources as $source) {
            foreach ($source->files() as $file) {
                $files[$file->group()][] = $file;
            }
        }

        $groups = [];

        // The repository is the truth: a file whose key was removed at runtime
        // is not listed, a key without file still is.
        foreach (array_keys($this->config->all()) as $key) {
            $key = (string) $key;

            if (! $this->isHiddenGroup($key)) {
                $groups[$key] = new ConfigGroup($key, $files[$key] ?? []);
            }
        }

        ksort($groups, SORT_NATURAL);

        return $groups;
    }

    private function groupMatches(ConfigGroup $group, ConfigQuery $query): bool
    {
        if ($query->group !== null && $query->group !== $group->key) {
            return false;
        }

        if ($query->origin !== null && ! $group->hasOrigin($query->origin)) {
            return false;
        }

        if ($query->module !== null && ! in_array($query->module, $group->modules(), true)) {
            return false;
        }

        return true;
    }

    private function makeEntry(string $key, string $group, mixed $value): ConfigEntry
    {
        if ($this->masker->shouldMask($key, $value)) {
            return new ConfigEntry($key, $group, null, $this->masker->placeholder(), ValueType::of($value), masked: true);
        }

        return new ConfigEntry($key, $group, $value, $this->formatter->format($value), ValueType::of($value));
    }

    private function isHiddenGroup(string $group): bool
    {
        foreach ($this->hidden as $pattern) {
            if (! str_contains($pattern, '.') && Str::is($pattern, $group)) {
                return true;
            }
        }

        return false;
    }

    private function isHidden(string $key): bool
    {
        if ($this->isHiddenGroup(explode('.', $key, 2)[0])) {
            return true;
        }

        foreach ($this->hidden as $pattern) {
            // A pattern hides the key itself and everything below it.
            if (Str::is($pattern, $key) || Str::is($pattern.'.*', $key)) {
                return true;
            }
        }

        return false;
    }
}
