<?php

namespace Bza\MigrationViewer\Filament\Schemas;

use Bza\MigrationViewer\Generator\ColumnType;
use Bza\MigrationViewer\Generator\NewColumn;
use Bza\MigrationViewer\Generator\OnDelete;
use Closure;
use Filament\Actions\Action;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Support\Icons\Heroicon;

/**
 * The columns of the create and edit pages. Each item becomes a NewColumn;
 * on the edit page an item also carries the column's original name ("original",
 * empty for a new column) and whether it can only be kept or dropped ("locked").
 */
final class NewColumnsRepeater
{
    private const KEY = 'migration-viewer::migration-viewer.generator.';

    /** The keys of a column's fields: an item keeps them in hidden fields. */
    private const FIELDS = ['name', 'type', 'length', 'total', 'places', 'references', 'on_delete', 'default', 'nullable', 'unique', 'index'];

    /**
     * An item shows its column in one line; adding or editing a column opens
     * its fields in a slide-over on the right.
     *
     * @param  bool  $editing  The edit page: items start from the current columns.
     */
    public static function make(bool $editing = false): Repeater
    {
        return Repeater::make('columns')
            ->label(__(self::KEY.'columns'))
            ->addActionLabel(__(self::KEY.'add_column'))
            ->addAction(fn (Action $action): Action => $action
                ->icon(Heroicon::OutlinedPlus)
                ->slideOver()
                ->modalHeading(__(self::KEY.'add_column'))
                ->modalSubmitActionLabel(__(self::KEY.'add'))
                ->schema(fn (Repeater $component): array => self::fields(taken: fn (): array => self::names($component)))
                ->action(function (array $data, Repeater $component): void {
                    $key = $component->generateUuid();
                    $items = $component->getRawState();
                    $item = self::item($data);

                    if ($key) {
                        $items[$key] = $item;
                    } else {
                        $items[] = $item;
                        $key = array_key_last($items);
                    }

                    self::store($component, $items, $key);
                }))
            ->extraItemActions([
                Action::make('editColumn')
                    ->label(__(self::KEY.'edit_column'))
                    ->icon(Heroicon::OutlinedPencilSquare)
                    ->visible(fn (array $arguments, Repeater $component): bool => empty($component->getRawState()[$arguments['item']]['locked'] ?? false))
                    ->slideOver()
                    ->modalHeading(fn (array $arguments, Repeater $component): string => __(self::KEY.'edit_column_named', ['column' => (string) ($component->getRawState()[$arguments['item']]['name'] ?? '')]))
                    ->modalSubmitActionLabel(__(self::KEY.'apply'))
                    ->fillForm(fn (array $arguments, Repeater $component): array => array_intersect_key($component->getRawState()[$arguments['item']] ?? [], array_flip(self::FIELDS)))
                    ->schema(fn (array $arguments, Repeater $component): array => self::fields(taken: fn (): array => self::names($component, except: $arguments['item'])))
                    ->action(function (array $data, array $arguments, Repeater $component): void {
                        $items = $component->getRawState();
                        $key = $arguments['item'];
                        // Replaced, not merged: a field hidden by the new type must not linger.
                        $items[$key] = self::item($data, original: $items[$key]['original'] ?? null, definition: $items[$key]['definition'] ?? null);

                        self::store($component, $items, $key);
                    }),
            ])
            ->itemLabel(fn (array $state): ?string => self::itemLabel($state))
            ->defaultItems(0)
            ->reorderable(false)
            ->schema([
                Hidden::make('original'),
                Hidden::make('locked')->default(false),
                ...array_map(fn (string $field): Hidden => Hidden::make($field), self::FIELDS),
                Text::make(fn (Get $get): string => $get('locked')
                    ? __(self::KEY.'locked', ['definition' => (string) $get('definition')])
                    : self::summary(fn (string $key): mixed => $get($key)))
                    ->color('gray'),
            ]);
    }

    /**
     * An item from the slide-over's data, every field present.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private static function item(array $data, ?string $original = null, ?string $definition = null): array
    {
        return [
            'original' => $original,
            'locked' => false,
            'definition' => $definition,
            ...array_fill_keys(self::FIELDS, null),
            ...array_intersect_key($data, array_flip(self::FIELDS)),
        ];
    }

    /**
     * @param  array<string, array<string, mixed>>  $items
     */
    private static function store(Repeater $repeater, array $items, string|int $key): void
    {
        $repeater->rawState($items);
        $repeater->getChildSchema($key)->fill($items[$key]);
        $repeater->callAfterStateUpdated();
    }

    /**
     * "string(30), nullable, default 'draft', unique", "foreignId → users, on delete cascade".
     *
     * @param  Closure(string): mixed  $get
     */
    private static function summary(Closure $get): string
    {
        $type = ColumnType::tryFrom((string) $get('type'));

        if ($type === null) {
            return '';
        }

        $parameters = match (true) {
            $type->hasLength() && filled($get('length')) => '('.$get('length').')',
            $type->hasPrecision() => '('.($get('total') ?: 8).', '.(filled($get('places')) ? $get('places') : 2).')',
            default => '',
        };

        $parts = [$type->value.$parameters];

        if ($type->isForeign()) {
            $parts[0] .= ' → '.(filled($get('references')) ? $get('references') : __(self::KEY.'references_guessed'));
        }

        foreach (['nullable', 'unique', 'index'] as $flag) {
            if ($get($flag)) {
                $parts[] = $flag;
            }
        }

        if (filled($get('default')) && $type->acceptsDefault()) {
            $parts[] = 'default '.$get('default');
        }

        if ($type->isForeign() && filled($get('on_delete'))) {
            $parts[] = 'on delete '.$get('on_delete');
        }

        return implode(', ', $parts);
    }

    /**
     * The fields of one column, in the slide-over.
     *
     * @param  (Closure(): list<string>)|null  $taken  The names the other columns use.
     * @return list<Component>
     */
    public static function fields(?Closure $taken = null): array
    {
        $name = TextInput::make('name')
            ->label(__(self::KEY.'name'))
            ->required()
            ->regex(NewColumn::NAME_PATTERN)
            ->validationMessages(['regex' => __(self::KEY.'name_format')])
            ->columnSpan(2);

        $name = $taken === null
            ? $name
            : $name->rule(fn (): Closure => function (string $attribute, mixed $value, Closure $fail) use ($taken): void {
                if (in_array($value, $taken(), true)) {
                    $fail(__(self::KEY.'name_taken'));
                }
            });

        return [
            Grid::make(4)->schema([
                $name,
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
            Grid::make(3)->schema([
                Toggle::make('nullable')->label(__(self::KEY.'nullable'))->live(),
                Toggle::make('unique')->label(__(self::KEY.'unique')),
                Toggle::make('index')
                    ->label(__(self::KEY.'index'))
                    ->visible(fn (Get $get): bool => ! (self::type($get)?->isForeign() ?? false)),
            ]),
        ];
    }

    /**
     * The names of the repeater's columns (a kept column by its original name).
     *
     * @return list<string>
     */
    private static function names(Repeater $repeater, string|int|null $except = null): array
    {
        $items = $repeater->getRawState() ?? [];
        unset($items[$except]);

        return array_values(array_filter(array_map(
            fn (array $item): ?string => ! empty($item['locked']) ? ($item['original'] ?? null) : ($item['name'] ?? null),
            $items,
        )));
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
