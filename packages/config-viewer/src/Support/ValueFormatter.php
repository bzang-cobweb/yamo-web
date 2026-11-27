<?php

namespace Bza\ConfigViewer\Support;

use BackedEnum;
use Closure;
use Stringable;
use UnitEnum;

/**
 * Turns any configuration value into readable text.
 */
class ValueFormatter
{
    public function format(mixed $value): string
    {
        return match (true) {
            is_string($value) => $value,
            is_bool($value) => $value ? 'true' : 'false',
            $value === null => 'null',
            is_int($value), is_float($value) => (string) $value,
            is_array($value) => $this->formatArray($value),
            $value instanceof BackedEnum => $value::class.'::'.$value->name.' ('.$value->value.')',
            $value instanceof UnitEnum => $value::class.'::'.$value->name,
            $value instanceof Closure => 'Closure',
            $value instanceof Stringable => (string) $value,
            default => get_debug_type($value),
        };
    }

    /**
     * @param  array<array-key, mixed>  $value
     */
    private function formatArray(array $value): string
    {
        // Nested objects or closures are not JSON-friendly: format each item first.
        $items = array_map(fn (mixed $item): mixed => is_scalar($item) || $item === null || is_array($item) ? $item : $this->format($item), $value);

        return (string) json_encode($items, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PARTIAL_OUTPUT_ON_ERROR);
    }
}
