<?php

namespace Bza\ConfigViewer\Data;

use Bza\ConfigViewer\Support\ValueType;

/**
 * One leaf of the configuration, ready to display. A masked entry carries no
 * value: only its placeholder reaches the adapters.
 */
final readonly class ConfigEntry
{
    public function __construct(
        /** The full dotted key, e.g. "mail.mailers.smtp.host". */
        public string $key,
        /** The top-level key the entry belongs to. */
        public string $group,
        /** The raw value, or null when masked. */
        public mixed $value,
        /** The value as text. */
        public string $display,
        public ValueType $type,
        public bool $masked = false,
    ) {}

    /** The key without its group, e.g. "mailers.smtp.host". */
    public function path(): string
    {
        return substr($this->key, strlen($this->group) + 1);
    }

    /**
     * Matches the term against the key and, unless masked, the displayed value.
     */
    public function matches(string $term): bool
    {
        if (str_contains(mb_strtolower($this->key), mb_strtolower($term))) {
            return true;
        }

        if ($this->masked) {
            return false;
        }

        return str_contains(mb_strtolower($this->display), mb_strtolower($term));
    }

    /**
     * @return array{key: string, group: string, path: string, display: string, type: string, masked: bool}
     */
    public function toArray(): array
    {
        return [
            'key' => $this->key,
            'group' => $this->group,
            'path' => $this->path(),
            'display' => $this->display,
            'type' => $this->type->value,
            'masked' => $this->masked,
        ];
    }
}
