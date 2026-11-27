<?php

namespace Bza\MigrationViewer\Replay;

use BackedEnum;
use Illuminate\Database\Grammar;
use Illuminate\Database\Query\Expression;
use Illuminate\Support\Fluent;
use UnitEnum;

/**
 * Describes a column definition in a short, Laravel-flavoured form:
 * "string(100), nullable, default 'draft'".
 */
class ColumnDescriber
{
    public function describe(Fluent $column, Grammar $grammar): string
    {
        $attributes = $column->getAttributes();
        $parts = [$this->type($attributes)];

        if (! empty($attributes['unsigned'])) {
            $parts[] = 'unsigned';
        }

        if (! empty($attributes['autoIncrement'])) {
            $parts[] = 'auto-increment';
        }

        if (! empty($attributes['nullable'])) {
            $parts[] = 'nullable';
        }

        if (array_key_exists('default', $attributes)) {
            $parts[] = 'default '.$this->value($attributes['default'], $grammar);
        }

        if (! empty($attributes['useCurrent'])) {
            $parts[] = 'default CURRENT_TIMESTAMP';
        }

        if (! empty($attributes['useCurrentOnUpdate'])) {
            $parts[] = 'on update CURRENT_TIMESTAMP';
        }

        foreach (['storedAs' => 'stored as', 'virtualAs' => 'virtual as'] as $key => $label) {
            if (isset($attributes[$key])) {
                $parts[] = $label.' '.$this->value($attributes[$key], $grammar, quote: false);
            }
        }

        if (isset($attributes['after'])) {
            $parts[] = 'after '.$attributes['after'];
        }

        if (isset($attributes['comment'])) {
            $parts[] = 'comment '.$this->value($attributes['comment'], $grammar);
        }

        return implode(', ', $parts);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function type(array $attributes): string
    {
        $type = (string) ($attributes['type'] ?? '?');

        $parameters = match (true) {
            isset($attributes['allowed']) => array_map(fn (mixed $value): string => "'".$value."'", (array) $attributes['allowed']),
            isset($attributes['length']) => [$attributes['length']],
            isset($attributes['total']) => array_values(array_filter([$attributes['total'], $attributes['places'] ?? null], fn (mixed $value): bool => $value !== null)),
            default => [],
        };

        return $parameters === [] ? $type : $type.'('.implode(', ', $parameters).')';
    }

    private function value(mixed $value, Grammar $grammar, bool $quote = true): string
    {
        return match (true) {
            $value instanceof Expression => (string) $value->getValue($grammar),
            $value instanceof BackedEnum => $this->value($value->value, $grammar, $quote),
            $value instanceof UnitEnum => $value->name,
            is_bool($value) => $value ? 'true' : 'false',
            $value === null => 'null',
            is_string($value) && $quote => "'".$value."'",
            is_scalar($value) => (string) $value,
            default => json_encode($value) ?: '?',
        };
    }
}
