<?php

namespace Bza\MigrationViewer\Replay;

use BackedEnum;
use Bza\MigrationViewer\ChangeKind;
use Bza\MigrationViewer\Data\ChangeDetail;
use Bza\MigrationViewer\Data\MigrationFile;
use Bza\MigrationViewer\Data\TableChange;
use Bza\MigrationViewer\Operation;
use Illuminate\Database\Grammar;
use Illuminate\Database\Query\Expression;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Fluent;

/**
 * Turns the blueprints recorded for one migration into one change per table.
 */
class BlueprintReader
{
    /**
     * Commands that only restate a column or the table operation, in lower case:
     * the grammars add "fluent" commands (AutoIncrementStartingValues, Comment)
     * for column attributes the column description already shows.
     */
    private const SKIPPED = ['create', 'drop', 'dropifexists', 'rename', 'add', 'change', 'autoincrementstartingvalues', 'comment'];

    private const INDEXES = ['primary', 'unique', 'index', 'fulltext', 'spatialIndex', 'vectorIndex'];

    private const DROPPED_INDEXES = ['dropPrimary', 'dropUnique', 'dropIndex', 'dropFullText', 'dropSpatialIndex', 'dropVectorIndex'];

    public function __construct(private readonly ColumnDescriber $describer) {}

    /**
     * @param  list<Blueprint>  $blueprints
     * @return list<TableChange>
     */
    public function read(array $blueprints, MigrationFile $migration, Grammar $grammar, ?bool $ran): array
    {
        /** @var array<string, array{operations: list<Operation>, details: list<ChangeDetail>, renamedTo: ?string}> $tables */
        $tables = [];

        foreach ($blueprints as $blueprint) {
            $table = $blueprint->getTable();
            $tables[$table] ??= ['operations' => [], 'details' => [], 'renamedTo' => null];
            $tables[$table]['operations'][] = $this->operation($blueprint);

            foreach ($blueprint->getColumns() as $column) {
                $tables[$table]['details'][] = new ChangeDetail(
                    $column->change ? ChangeKind::ChangeColumn : ChangeKind::AddColumn,
                    (string) $column->name,
                    $this->describer->describe($column, $grammar),
                    attributes: $this->columnAttributes($column, $grammar),
                );
            }

            foreach ($blueprint->getCommands() as $command) {
                if ($command->name === 'rename') {
                    $tables[$table]['renamedTo'] = (string) $command->to;
                }

                array_push($tables[$table]['details'], ...$this->details($command));
            }
        }

        $changes = [];

        foreach ($tables as $table => $change) {
            $changes[] = new TableChange(
                table: $table,
                migration: $migration,
                operation: $this->strongest($change['operations']),
                details: $change['details'],
                ran: $ran,
                renamedTo: $change['renamedTo'],
            );
        }

        return $changes;
    }

    private function operation(Blueprint $blueprint): Operation
    {
        $names = array_map(fn (Fluent $command): string => (string) $command->name, $blueprint->getCommands());

        return match (true) {
            in_array('create', $names, true) => Operation::Create,
            in_array('drop', $names, true), in_array('dropIfExists', $names, true) => Operation::Drop,
            in_array('rename', $names, true) => Operation::Rename,
            default => Operation::Alter,
        };
    }

    /**
     * Several blueprints on one table within a migration: a creation wins
     * (create then alter is still a creation), then a drop, then a rename.
     *
     * @param  list<Operation>  $operations
     */
    private function strongest(array $operations): Operation
    {
        foreach ([Operation::Create, Operation::Drop, Operation::Rename] as $operation) {
            if (in_array($operation, $operations, true)) {
                return $operation;
            }
        }

        return Operation::Alter;
    }

    /**
     * @return list<ChangeDetail>
     */
    private function details(Fluent $command): array
    {
        $name = (string) $command->name;

        if (in_array(strtolower($name), self::SKIPPED, true) || $command->shouldBeSkipped) {
            return $name === 'rename' ? [new ChangeDetail(ChangeKind::RenameTable, (string) $command->to)] : [];
        }

        if (in_array($name, self::INDEXES, true)) {
            return [new ChangeDetail(
                ChangeKind::AddIndex,
                (string) $command->index,
                $name.' ('.implode(', ', (array) $command->columns).')',
                attributes: ['type' => $name, 'columns' => array_values((array) $command->columns)],
            )];
        }

        if (in_array($name, self::DROPPED_INDEXES, true)) {
            return [new ChangeDetail(ChangeKind::DropIndex, $this->list($command->index))];
        }

        return match ($name) {
            'foreign' => [new ChangeDetail(ChangeKind::AddForeign, (string) $command->index, $this->foreign($command), attributes: [
                'columns' => array_values((array) $command->columns),
                'on' => (string) $command->on,
                'references' => array_values((array) $command->references),
                'onDelete' => $command->onDelete,
            ])],
            'dropForeign' => [new ChangeDetail(ChangeKind::DropForeign, $this->list($command->index))],
            'dropColumn' => array_map(fn (string $column): ChangeDetail => new ChangeDetail(ChangeKind::DropColumn, $column), (array) $command->columns),
            'renameColumn' => [new ChangeDetail(ChangeKind::RenameColumn, (string) $command->from, '→ '.$command->to, (string) $command->to)],
            default => [new ChangeDetail(ChangeKind::Other, $name)],
        };
    }

    /**
     * The column's attributes, with its default made plain: an expression
     * becomes its SQL text, flagged as such.
     *
     * @return array<string, mixed>
     */
    private function columnAttributes(Fluent $column, Grammar $grammar): array
    {
        $attributes = $column->getAttributes();
        unset($attributes['change']);

        if (array_key_exists('default', $attributes)) {
            $default = $attributes['default'];
            $attributes['defaultIsExpression'] = $default instanceof Expression;
            $attributes['default'] = match (true) {
                $default instanceof Expression => (string) $default->getValue($grammar),
                $default instanceof BackedEnum => $default->value,
                is_scalar($default) || $default === null => $default,
                default => json_encode($default),
            };
        }

        // Objects (enums of allowed values aside) are not kept.
        return array_filter($attributes, fn (mixed $value): bool => ! is_object($value));
    }

    /**
     * "(user_id) → users(id), on delete cascade".
     */
    private function foreign(Fluent $command): string
    {
        $definition = '('.implode(', ', (array) $command->columns).') → '.$command->on.'('.implode(', ', (array) $command->references).')';

        foreach (['onDelete' => 'on delete', 'onUpdate' => 'on update'] as $key => $label) {
            if ($command->{$key} !== null) {
                $definition .= ', '.$label.' '.$command->{$key};
            }
        }

        return $definition;
    }

    /**
     * An index may be dropped by name or by its columns.
     */
    private function list(mixed $value): string
    {
        return is_array($value) ? implode(', ', $value) : (string) $value;
    }
}
