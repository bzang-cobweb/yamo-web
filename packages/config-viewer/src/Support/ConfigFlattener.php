<?php

namespace Bza\ConfigViewer\Support;

/**
 * Flattens a nested configuration array into dotted keys.
 *
 * Associative arrays are walked into. A list of plain values (e.g. allowed MIME
 * types) stays one entry, as does an empty array; a list of arrays is walked
 * into by index.
 */
class ConfigFlattener
{
    /**
     * @return array<string, mixed>
     */
    public function flatten(mixed $value, string $prefix): array
    {
        if (! is_array($value) || $value === [] || $this->isListOfPlainValues($value)) {
            return [$prefix => $value];
        }

        $flat = [];

        foreach ($value as $key => $item) {
            $flat += $this->flatten($item, $prefix.'.'.$key);
        }

        return $flat;
    }

    /**
     * @param  array<array-key, mixed>  $value
     */
    private function isListOfPlainValues(array $value): bool
    {
        if (! array_is_list($value)) {
            return false;
        }

        foreach ($value as $item) {
            if (is_array($item)) {
                return false;
            }
        }

        return true;
    }
}
