<?php

namespace Bza\MigrationViewer\Filament\Schemas;

use Bza\MigrationViewer\Generator\ColumnType;
use Bza\MigrationViewer\Generator\NewColumn;
use Bza\MigrationViewer\Generator\OnDelete;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Components\Utilities\Get;

/**
 * The columns of the create and edit pages. Each item becomes a NewColumn;
 * on the edit page an item also carries the column's original name ("original",
 * empty for a new column) and whether it can only be kept or dropped ("locked").
 */
final class NewColumnsRepeater
{
    private const KEY = 'migration-viewer::migration-viewer.generator.';

    /**
     * @param  bool  $editing  The edit page: items start from the current columns.
     */
    public static function make(bool $editing = false): Repeater
    {
        $locked = fn (Get $get): bool => (bool) $get('locked');

        return Repeater::make('columns')
            ->label(__(self::KEY.'columns'))
            ->addActionLabel(__(self::KEY.'add_column'))
            ->itemLabel(fn (array $state): ?string => self::itemLabel($state))
            ->defaultItems($editing ? 0 : 1)
            ->reorderable(false)
            ->collapsible()
            ->schema([
                Hidden::make('original'),
                Hidden::make('locked')->default(false),
                Text::make(fn (Get $get): string => __(self::KEY.'locked', ['definition' => (string) $get('definition')]))
                    ->color('gray')
                    ->visible($locked),
                Grid::make(4)->hidden($locked)->schema([
                    TextInput::make('name')
                        ->label(__(self::KEY.'name'))
                        ->required()
                        ->regex(NewColumn::NAME_PATTERN)
                        ->validationMessages(['regex' => __(self::KEY.'name_format')])
                        ->distinct()
                        ->columnSpan(2),
                    Select::make('type')
                        ->label(__(self::KEY.'type'))
                        ->options(collect(ColumnType::cases())->mapWithKeys(fn (ColumnType $type): array => [$type->value => $type->value])->all())
                        ->default(ColumnType::String->value)
                        ->required()
                        ->searchable()
                        ->live()
                        ->columnSpan(2),
                    TextInput::make('length')
                        ->label(__(self::KEY.'length'))
                        ->integer()
                        ->minValue(1)
                        ->placeholder('255')
                        ->visible(fn (Get $get): bool => self::type($get)?->hasLength() ?? false),
                    TextInput::make('total')
                        ->label(__(self::KEY.'total'))
                        ->integer()
                        ->minValue(1)
                        ->placeholder('8')
                        ->visible(fn (Get $get): bool => self::type($get)?->hasPrecision() ?? false),
                    TextInput::make('places')
                        ->label(__(self::KEY.'places'))
                        ->integer()
                        ->minValue(0)
                        ->placeholder('2')
                        ->visible(fn (Get $get): bool => self::type($get)?->hasPrecision() ?? false),
                    TextInput::make('references')
                        ->label(__(self::KEY.'references'))
                        ->regex(NewColumn::NAME_PATTERN)
                        ->placeholder(__(self::KEY.'references_guessed'))
                        ->visible(fn (Get $get): bool => self::type($get)?->isForeign() ?? false),
                    Select::make('on_delete')
                        ->label(__(self::KEY.'on_delete'))
                        ->options(collect(OnDelete::cases())->mapWithKeys(fn (OnDelete $onDelete): array => [$onDelete->value => $onDelete->value])->all())
                        ->visible(fn (Get $get): bool => self::type($get)?->isForeign() ?? false),
                    TextInput::make('default')
                        ->label(__(self::KEY.'default'))
                        ->visible(fn (Get $get): bool => self::type($get)?->acceptsDefault() ?? true)
                        ->rule(fn (Get $get): ?string => self::type($get)?->isNumeric() ? 'numeric' : null),
                ]),
                Grid::make(3)->hidden($locked)->schema([
                    Toggle::make('nullable')->label(__(self::KEY.'nullable'))->live(),
                    Toggle::make('unique')->label(__(self::KEY.'unique')),
                    Toggle::make('index')
                        ->label(__(self::KEY.'index'))
                        ->visible(fn (Get $get): bool => ! (self::type($get)?->isForeign() ?? false)),
                ]),
            ]);
    }

    /**
     * "title · string", "title → label · string", "status (kept as is)"…
     *
     * @param  array<string, mixed>  $state
     */
    private static function itemLabel(array $state): ?string
    {
        $name = (string) ($state['name'] ?? '');
        $original = (string) ($state['original'] ?? '');

        if (! empty($state['locked'])) {
            return $original.' · '.__(self::KEY.'kept');
        }

        if ($name === '') {
            return null;
        }

        $label = $original !== '' && $original !== $name ? $original.' → '.$name : $name;

        return $label.' · '.($state['type'] ?? '').($original === '' ? ' · '.__(self::KEY.'new') : '');
    }

    /**
     * @param  array<int|string, array<string, mixed>>  $items
     * @return list<NewColumn>
     */
    public static function columns(array $items): array
    {
        return array_values(array_map(fn (array $item): NewColumn => NewColumn::fromArray($item), $items));
    }

    private static function type(Get $get): ?ColumnType
    {
        return ColumnType::tryFrom((string) $get('type'));
    }
}
