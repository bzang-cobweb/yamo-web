<?php

namespace Bza\MigrationViewer;

/**
 * One kind of change inside a table operation.
 */
enum ChangeKind: string
{
    case AddColumn = 'add_column';

    case ChangeColumn = 'change_column';

    case RenameColumn = 'rename_column';

    case DropColumn = 'drop_column';

    case AddIndex = 'add_index';

    case DropIndex = 'drop_index';

    case AddForeign = 'add_foreign';

    case DropForeign = 'drop_foreign';

    case RenameTable = 'rename_table';

    /** Any other blueprint command (comment, engine, renameIndex…). */
    case Other = 'other';

    /**
     * The sign shown before the change: added, changed or removed.
     */
    public function sign(): string
    {
        return match ($this) {
            self::AddColumn, self::AddIndex, self::AddForeign => '+',
            self::DropColumn, self::DropIndex, self::DropForeign => '−',
            default => '~',
        };
    }
}
