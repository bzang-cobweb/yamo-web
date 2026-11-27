<?php

namespace Bza\MigrationViewer\Generator;

/**
 * Writes the PHP source of a migration, in the style of "make:migration":
 * an anonymous class with up() and down().
 */
class MigrationCodeWriter
{
    public function create(NewTable $table): string
    {
        $lines = [];

        if ($table->id) {
            $lines[] = '$table->id();';
        }

        foreach ($table->columns as $column) {
            $lines[] = $this->column($column);
        }

        if ($table->timestamps) {
            $lines[] = '$table->timestamps();';
        }

        if ($table->softDeletes) {
            $lines[] = '$table->softDeletes();';
        }

        $up = $this->schema('create', $table->name, $lines);
        $down = "        Schema::dropIfExists('{$table->name}');";

        return $this->migration($up, $down);
    }

    /**
     * @param  list<NewColumn>  $columns
     */
    public function addColumns(string $table, array $columns): string
    {
        $up = $this->schema('table', $table, array_map(fn (NewColumn $column): string => $this->column($column), $columns));

        $drops = [];
        $plain = [];

        foreach ($columns as $column) {
            if ($column->type->isForeign()) {
                $drops[] = "\$table->dropConstrainedForeignId('{$column->name}');";
            } else {
                $plain[] = "'{$column->name}'";
            }
        }

        if ($plain !== []) {
            $drops[] = count($plain) === 1 ? "\$table->dropColumn({$plain[0]});" : '$table->dropColumn(['.implode(', ', $plain).']);';
        }

        return $this->migration($up, $this->schema('table', $table, $drops));
    }

    /**
     * All the changes of one edit, in one migration: up() applies them, down()
     * undoes them (a dropped column the generator cannot write is not restored).
     */
    public function alter(TableAlteration $alteration): string
    {
        $up = [];
        $down = [];

        // up: keys first, then columns: drop, rename, change, add.
        foreach ($alteration->drops as $state) {
            array_push($up, ...$this->dropKeys($state));
        }

        foreach ($alteration->changes as ['from' => $from, 'to' => $to]) {
            if ($from->foreignKey !== null && ! $this->sameForeign($from->spec, $to)) {
                $up[] = $this->dropForeign($from->name, $from->foreignKey);
            }

            if ($from->uniqueIndex !== null && ! $to->unique) {
                $up[] = "\$table->dropUnique('{$from->uniqueIndex}');";
            }

            if ($from->plainIndex !== null && ! $to->index) {
                $up[] = "\$table->dropIndex('{$from->plainIndex}');";
            }
        }

        if ($alteration->drops !== []) {
            $up[] = $this->dropColumns(array_map(fn (ColumnState $state): string => $state->name, $alteration->drops));
        }

        foreach ($alteration->renames as $from => $to) {
            $up[] = "\$table->renameColumn('{$from}', '{$to}');";
        }

        foreach ($alteration->changes as ['from' => $from, 'to' => $to]) {
            $up[] = $this->changedColumn($to);
            array_push($up, ...$this->addedKeys($to, $from->uniqueIndex !== null, $from->plainIndex !== null, $from->foreignKey !== null && $this->sameForeign($from->spec, $to)));
        }

        foreach ($alteration->adds as $column) {
            $up[] = $this->column($column);
        }

        // down: the same steps undone, in reverse order.
        foreach ($alteration->adds as $column) {
            // Its indexes first: SQLite cannot drop an indexed column.
            if ($column->unique) {
                $down[] = "\$table->dropUnique(['{$column->name}']);";
            } elseif ($column->index && ! $column->type->isForeign()) {
                $down[] = "\$table->dropIndex(['{$column->name}']);";
            }

            $down[] = $column->type->isForeign() ? "\$table->dropConstrainedForeignId('{$column->name}');" : "\$table->dropColumn('{$column->name}');";
        }

        foreach ($alteration->changes as ['from' => $from, 'to' => $to]) {
            $original = $from->spec->withName($to->name);
            $keptForeign = $from->foreignKey !== null && $this->sameForeign($from->spec, $to);

            if ($to->type->isForeign() && ! $keptForeign) {
                $down[] = "\$table->dropForeign(['{$to->name}']);";
            }

            if ($to->unique && $from->uniqueIndex === null) {
                $down[] = "\$table->dropUnique(['{$to->name}']);";
            }

            if ($to->index && $from->plainIndex === null) {
                $down[] = "\$table->dropIndex(['{$to->name}']);";
            }

            $down[] = $this->changedColumn($original);
            array_push($down, ...$this->restoredKeys($from, $to->name, restoreForeign: ! $keptForeign, restoreUnique: ! $to->unique, restoreIndex: ! $to->index));
        }

        foreach (array_reverse($alteration->renames, true) as $from => $to) {
            $down[] = "\$table->renameColumn('{$to}', '{$from}');";
        }

        foreach ($alteration->drops as $state) {
            if ($state->spec === null) {
                $down[] = "// {$state->name} ({$state->definition}) cannot be restored by the generator.";

                continue;
            }

            $down[] = $this->column($state->spec->withName($state->name), withKeys: false);
            array_push($down, ...$this->restoredKeys($state, $state->name, restoreForeign: true, restoreUnique: true, restoreIndex: true));
        }

        $table = $alteration->newName;
        $upCode = ($alteration->renamesTable() ? "        Schema::rename('{$alteration->table}', '{$table}');\n\n" : '').$this->schemaOrNothing($table, $up);
        $downCode = $this->schemaOrNothing($table, $down).($alteration->renamesTable() ? "\n\n        Schema::rename('{$table}', '{$alteration->table}');" : '');

        return $this->migration(rtrim($upCode), ltrim($downCode, "\n") === '' ? '        //' : $downCode);
    }

    /**
     * One Blueprint statement: "$table->string('title', 100)->nullable();".
     * Without keys, unique, index and the foreign key are left out.
     */
    public function column(NewColumn $column, bool $withKeys = true): string
    {
        $arguments = ["'{$column->name}'"];

        if ($column->type->hasLength() && $column->length !== null) {
            $arguments[] = (string) $column->length;
        }

        if ($column->type->hasPrecision()) {
            $arguments[] = (string) ($column->total ?? 8);
            $arguments[] = (string) ($column->places ?? 2);
        }

        $code = '$table->'.$column->type->value.'('.implode(', ', $arguments).')';

        if ($column->nullable) {
            $code .= '->nullable()';
        }

        if ($column->hasDefault()) {
            $code .= '->default('.$this->defaultValue($column).')';
        }

        if (! $withKeys) {
            return $code.';';
        }

        if ($column->unique) {
            $code .= '->unique()';
        } elseif ($column->index && ! $column->type->isForeign()) {
            // constrained() already indexes a foreign id.
            $code .= '->index()';
        }

        if ($column->type->isForeign()) {
            $code .= $column->references !== null ? "->constrained('{$column->references}')" : '->constrained()';

            if ($column->onDelete !== null) {
                $code .= '->'.$column->onDelete->method().'()';
            }
        }

        return $code.';';
    }

    /**
     * The full new definition of an existing column: change() replaces every
     * attribute, so nullable and default are always restated.
     */
    private function changedColumn(NewColumn $column): string
    {
        // A foreign id is an unsigned big integer; its key is handled apart.
        $column = $column->type->isForeign()
            ? new NewColumn($column->name, ColumnType::UnsignedBigInteger, nullable: $column->nullable)
            : $column;

        return substr($this->column($column, withKeys: false), 0, -1).'->change();';
    }

    /**
     * @return list<string>
     */
    private function dropKeys(ColumnState $state): array
    {
        return array_values(array_filter([
            $state->foreignKey !== null ? $this->dropForeign($state->name, $state->foreignKey) : null,
            $state->uniqueIndex !== null ? "\$table->dropUnique('{$state->uniqueIndex}');" : null,
            $state->plainIndex !== null ? "\$table->dropIndex('{$state->plainIndex}');" : null,
        ]));
    }

    /**
     * The keys a changed column gains.
     *
     * @return list<string>
     */
    private function addedKeys(NewColumn $to, bool $hadUnique, bool $hadIndex, bool $keptForeign): array
    {
        return array_values(array_filter([
            $to->unique && ! $hadUnique ? "\$table->unique('{$to->name}');" : null,
            $to->index && ! $hadIndex ? "\$table->index('{$to->name}');" : null,
            $to->type->isForeign() && ! $keptForeign ? $this->foreign($to) : null,
        ]));
    }

    /**
     * The keys a column had, under their original names.
     *
     * @return list<string>
     */
    private function restoredKeys(ColumnState $state, string $column, bool $restoreForeign, bool $restoreUnique, bool $restoreIndex): array
    {
        return array_values(array_filter([
            $restoreUnique && $state->uniqueIndex !== null ? "\$table->unique('{$column}', '{$state->uniqueIndex}');" : null,
            $restoreIndex && $state->plainIndex !== null ? "\$table->index('{$column}', '{$state->plainIndex}');" : null,
            $restoreForeign && $state->foreignKey !== null && $state->spec !== null ? $this->foreign($state->spec->withName($column), $state->foreignKey) : null,
        ]));
    }

    private function foreign(NewColumn $column, ?string $name = null): string
    {
        $code = "\$table->foreign('{$column->name}'".($name !== null ? ", '{$name}'" : '').")->references('id')->on('{$column->referencedTable()}')";

        return $code.($column->onDelete !== null ? '->'.$column->onDelete->method().'()' : '').';';
    }

    /**
     * By its column, which SQLite needs, and by its real name, which the other
     * drivers need: a key keeps the name it got when it was created, even
     * after the table was renamed.
     */
    private function dropForeign(string $column, string $name): string
    {
        return "\$table->dropForeign(['{$column}'])->index('{$name}');";
    }

    private function sameForeign(?NewColumn $from, NewColumn $to): bool
    {
        return $from !== null
            && $to->type->isForeign()
            && $from->referencedTable() === $to->referencedTable()
            && $from->onDelete === $to->onDelete;
    }

    /**
     * @param  list<string>  $names
     */
    private function dropColumns(array $names): string
    {
        $quoted = array_map(fn (string $name): string => "'{$name}'", $names);

        return count($quoted) === 1 ? "\$table->dropColumn({$quoted[0]});" : '$table->dropColumn(['.implode(', ', $quoted).']);';
    }

    /**
     * @param  list<string>  $lines
     */
    private function schemaOrNothing(string $table, array $lines): string
    {
        return $lines === [] ? '' : $this->schema('table', $table, $lines);
    }

    private function defaultValue(NewColumn $column): string
    {
        $value = (string) $column->default;

        return match (true) {
            $column->type === ColumnType::Boolean => in_array(strtolower($value), ['1', 'true', 'yes', 'oui'], true) ? 'true' : 'false',
            $column->type->isInteger() && is_numeric($value) => (string) (int) $value,
            $column->type->isNumeric() && is_numeric($value) => (string) (float) $value,
            default => var_export($value, true),
        };
    }

    /**
     * @param  list<string>  $lines
     */
    private function schema(string $method, string $table, array $lines): string
    {
        $body = implode("\n", array_map(fn (string $line): string => '            '.$line, $lines));

        return "        Schema::{$method}('{$table}', function (Blueprint \$table) {\n{$body}\n        });";
    }

    private function migration(string $up, string $down): string
    {
        return <<<PHP
        <?php

        use Illuminate\Database\Migrations\Migration;
        use Illuminate\Database\Schema\Blueprint;
        use Illuminate\Support\Facades\Schema;

        return new class extends Migration
        {
            public function up(): void
            {
        {$up}
            }

            public function down(): void
            {
        {$down}
            }
        };

        PHP;
    }
}
