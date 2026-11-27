<?php

namespace Bza\MigrationViewer\Generator;

use Bza\MigrationViewer\Data\TableSummary;

/**
 * Reads the columns of a table, as the migrations leave it, back into specs
 * the generator can write again.
 */
final class CurrentColumns
{
    /**
     * @return array<string, ColumnState> Keyed by column name, in table order.
     */
    public static function of(TableSummary $table): array
    {
        $states = [];

        foreach ($table->columnAttributes as $name => $attributes) {
            $uniqueIndex = self::singleColumnIndex($table, $name, 'unique');
            $plainIndex = self::singleColumnIndex($table, $name, 'index');
            $foreignKey = self::foreignKey($table, $name);

            $states[$name] = new ColumnState(
                name: $name,
                spec: self::spec($name, $attributes, $uniqueIndex !== null, $plainIndex !== null, $foreignKey !== null ? $table->foreignKeys[$foreignKey] : null),
                definition: $table->columns[$name] ?? '',
                uniqueIndex: $uniqueIndex,
                plainIndex: $plainIndex,
                foreignKey: $foreignKey,
            );
        }

        return $states;
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @param  array<string, mixed>|null  $foreign
     */
    private static function spec(string $name, array $attributes, bool $unique, bool $index, ?array $foreign): ?NewColumn
    {
        if (! empty($attributes['autoIncrement'])
            || ! empty($attributes['useCurrent'])
            || ! empty($attributes['useCurrentOnUpdate'])
            || ! empty($attributes['defaultIsExpression'])
            || isset($attributes['storedAs'])
            || isset($attributes['virtualAs'])) {
            return null;
        }

        $type = self::type($attributes, $foreign);

        if ($type === null || preg_match(NewColumn::NAME_PATTERN, $name) !== 1) {
            return null;
        }

        $onDelete = $foreign !== null ? OnDelete::tryFrom(strtolower((string) ($foreign['onDelete'] ?? ''))) : null;

        // A foreign key on another column than "id", or a rule the generator does not write, cannot be rewritten.
        if ($foreign !== null && ($foreign['references'] !== ['id'] || (($foreign['onDelete'] ?? null) !== null && $onDelete === null))) {
            return null;
        }

        $default = array_key_exists('default', $attributes) ? $attributes['default'] : null;

        $nullable = ! empty($attributes['nullable']);

        if ($onDelete === OnDelete::SetNull && ! $nullable) {
            return null;
        }

        return new NewColumn(
            name: $name,
            type: $type,
            length: $type->hasLength() && isset($attributes['length']) ? (int) $attributes['length'] : null,
            total: $type->hasPrecision() && isset($attributes['total']) ? (int) $attributes['total'] : null,
            places: $type->hasPrecision() && isset($attributes['places']) ? (int) $attributes['places'] : null,
            nullable: $nullable,
            default: match (true) {
                $default === null => null,
                is_bool($default) => $default ? 'true' : 'false',
                default => (string) $default,
            },
            unique: $unique,
            index: $index && ! $type->isForeign(),
            references: $foreign !== null ? $foreign['on'] : null,
            onDelete: $onDelete,
        );
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @param  array<string, mixed>|null  $foreign
     */
    private static function type(array $attributes, ?array $foreign): ?ColumnType
    {
        $type = (string) ($attributes['type'] ?? '');
        $unsigned = ! empty($attributes['unsigned']);

        if ($foreign !== null) {
            return $type === 'bigInteger' && $unsigned ? ColumnType::ForeignId : null;
        }

        return match (true) {
            $type === 'bigInteger' && $unsigned => ColumnType::UnsignedBigInteger,
            $type === 'integer' && $unsigned => ColumnType::UnsignedInteger,
            $unsigned => null,
            // float(precision) and double are written without their precision.
            $type === 'float' && isset($attributes['precision']) && (int) $attributes['precision'] !== 53 => null,
            default => ColumnType::tryFrom($type),
        };
    }

    private static function singleColumnIndex(TableSummary $table, string $column, string $type): ?string
    {
        foreach ($table->indexes as $name => $index) {
            if ($index['type'] === $type && $index['columns'] === [$column]) {
                return $name;
            }
        }

        return null;
    }

    private static function foreignKey(TableSummary $table, string $column): ?string
    {
        foreach ($table->foreignKeys as $name => $key) {
            if ($key['columns'] === [$column]) {
                return $name;
            }
        }

        return null;
    }
}
