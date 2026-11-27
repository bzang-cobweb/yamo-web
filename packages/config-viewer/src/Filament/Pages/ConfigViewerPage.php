<?php

namespace Bza\ConfigViewer\Filament\Pages;

use BackedEnum;
use Bza\ConfigViewer\ConfigInspector;
use Bza\ConfigViewer\Data\ConfigEntry;
use Bza\ConfigViewer\Data\ConfigFile;
use Bza\ConfigViewer\Data\ConfigGroup;
use Bza\ConfigViewer\Data\ConfigQuery;
use Bza\ConfigViewer\Filament\ConfigViewerPlugin;
use Bza\ConfigViewer\Origin;
use Bza\ConfigViewer\Support\ValueType;
use Filament\Pages\Page;
use Filament\Panel;
use Filament\Schemas\Components\EmbeddedTable;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontFamily;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\Indicator;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Pagination\LengthAwarePaginator;
use UnitEnum;

/**
 * Read-only table of the effective configuration. All the logic lives in the
 * core (ConfigInspector): this page only maps entries to table rows.
 */
class ConfigViewerPage extends Page implements HasTable
{
    /** The type filter's option that keeps every value type. */
    private const ALL_TYPES = 'all';

    use InteractsWithTable {
        updatedTableFilters as baseUpdatedTableFilters;
    }

    public static function canAccess(): bool
    {
        return ConfigViewerPlugin::get()->isAuthorized();
    }

    public static function getSlug(?Panel $panel = null): string
    {
        return ConfigViewerPlugin::get()->getSlug();
    }

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return ConfigViewerPlugin::get()->getNavigationGroup();
    }

    public static function getNavigationIcon(): string|BackedEnum|Htmlable|null
    {
        return ConfigViewerPlugin::get()->getNavigationIcon();
    }

    public static function getNavigationSort(): ?int
    {
        return ConfigViewerPlugin::get()->getNavigationSort();
    }

    public static function getNavigationLabel(): string
    {
        return ConfigViewerPlugin::get()->getNavigationLabel();
    }

    public function getTitle(): string|Htmlable
    {
        return static::getNavigationLabel();
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([EmbeddedTable::make()]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->records(fn (?string $search, ?array $filters, ?string $sortDirection, int|string $page, int|string $recordsPerPage): LengthAwarePaginator => $this->records($search, $filters ?? [], $sortDirection, (int) $page, $recordsPerPage))
            ->columns([
                TextColumn::make('key')
                    ->label(__('config-viewer::config-viewer.columns.key'))
                    ->fontFamily(FontFamily::Mono)
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->wrap(),
                TextColumn::make('display')
                    ->label(__('config-viewer::config-viewer.columns.value'))
                    ->fontFamily(FontFamily::Mono)
                    ->color(fn (array $record): ?string => $record['masked'] ? 'gray' : null)
                    ->icon(fn (array $record): ?Heroicon => $record['masked'] ? Heroicon::OutlinedLockClosed : null)
                    ->tooltip(fn (array $record): ?string => $record['masked'] ? __('config-viewer::config-viewer.masked') : null)
                    ->copyable(fn (array $record): bool => ! $record['masked'])
                    ->wrap(),
                TextColumn::make('type')
                    ->label(__('config-viewer::config-viewer.columns.type'))
                    ->formatStateUsing(fn (string $state): string => ValueType::tryFrom($state)?->label() ?? $state)
                    ->badge()
                    ->color('gray'),
                TextColumn::make('origin')
                    ->label(__('config-viewer::config-viewer.columns.origin'))
                    ->badge(),
            ])
            ->filters([
                SelectFilter::make('group')
                    ->label(__('config-viewer::config-viewer.filters.group'))
                    ->options(fn (): array => array_map(fn (ConfigGroup $group): string => $this->groupLabel($group), $this->inspector()->groups()))
                    ->searchable(),
                SelectFilter::make('origin')
                    ->label(__('config-viewer::config-viewer.filters.origin'))
                    ->options(fn (): array => collect(Origin::cases())->mapWithKeys(fn (Origin $origin): array => [$origin->value => $origin->label()])->all()),
                SelectFilter::make('module')
                    ->label(__('config-viewer::config-viewer.filters.module'))
                    ->options(fn (): array => array_combine($this->inspector()->modules(), $this->inspector()->modules()))
                    ->searchable(),
                // A single choice, "All" by default, instead of an empty placeholder.
                SelectFilter::make('type')
                    ->label(__('config-viewer::config-viewer.filters.type'))
                    ->options(fn (): array => [self::ALL_TYPES => __('config-viewer::config-viewer.all'), ...$this->typeOptions()])
                    ->default(self::ALL_TYPES)
                    ->selectablePlaceholder(false)
                    ->indicateUsing(fn (array $state): array => $this->typeIndicators($state['value'] ?? null)),
            ], layout: FiltersLayout::AboveContent)
            // The module field is hidden here rather than with the filter's visible(): the filters form is built
            // before the new origin is applied, so a hidden filter would only appear on the next request.
            ->filtersFormSchema(function (array $filters): array {
                $filters['module']->visible(fn (): bool => $this->isModuleOriginSelected($this->tableFilters ?? []) && $this->inspector()->modules() !== []);

                return array_values($filters);
            })
            ->deferFilters(false)
            ->description(fn (): ?string => $this->selectedGroupDescription())
            ->defaultSort('key')
            ->paginated([25, 50, 100, 'all'])
            ->defaultPaginationPageOption(50)
            ->emptyStateHeading(__('config-viewer::config-viewer.empty'));
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function records(?string $search, array $filters, ?string $sortDirection, int $page, int|string $perPage): LengthAwarePaginator
    {
        $entries = $this->inspector()->entries(new ConfigQuery(
            group: ($filters['group']['value'] ?? null) ?: null,
            origin: Origin::tryFrom((string) ($filters['origin']['value'] ?? '')),
            // A hidden filter is ignored: the module only counts with the "Module" origin.
            module: $this->isModuleOriginSelected($filters) ? (($filters['module']['value'] ?? null) ?: null) : null,
            // "All" is no value type: tryFrom() gives null, hence no constraint.
            types: array_values(array_filter([ValueType::tryFrom((string) ($filters['type']['value'] ?? ''))])),
            search: $search,
        ));

        if ($sortDirection === 'desc') {
            $entries = array_reverse($entries);
        }

        $total = count($entries);
        $perPage = $perPage === 'all' ? max($total, 1) : (int) $perPage;

        $rows = [];

        foreach (array_slice($entries, ($page - 1) * $perPage, $perPage) as $entry) {
            $rows[$entry->key] = $this->row($entry);
        }

        return new LengthAwarePaginator($rows, $total, $perPage, $page);
    }

    /**
     * Clears the module when the "Module" origin is unselected, so that no
     * indicator is left for a filter that is no longer shown.
     */
    public function updatedTableFilters(): void
    {
        if (! $this->isModuleOriginSelected($this->tableFilters ?? [])) {
            $this->tableFilters['module']['value'] = null;
        }

        $this->baseUpdatedTableFilters();
    }

    /**
     * @return array<string, string>
     */
    private function typeOptions(): array
    {
        return collect(ValueType::cases())->mapWithKeys(fn (ValueType $type): array => [$type->value => $type->label()])->all();
    }

    /**
     * No indicator for "All": the filter then narrows nothing.
     *
     * @return array<Indicator>
     */
    private function typeIndicators(?string $value): array
    {
        $type = ValueType::tryFrom((string) $value);

        if ($type === null) {
            return [];
        }

        return [Indicator::make(__('config-viewer::config-viewer.filters.type').': '.$type->label())];
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function isModuleOriginSelected(array $filters): bool
    {
        return ($filters['origin']['value'] ?? null) === Origin::Module->value;
    }

    /**
     * @return array<string, mixed>
     */
    private function row(ConfigEntry $entry): array
    {
        $group = $this->inspector()->group($entry->group);

        return [
            '__key' => $entry->key,
            ...$entry->toArray(),
            'origin' => $group !== null ? $this->originLabel($group) : null,
        ];
    }

    private function groupLabel(ConfigGroup $group): string
    {
        return $group->key.' — '.$this->originLabel($group);
    }

    private function originLabel(ConfigGroup $group): string
    {
        return collect($group->origins())
            ->map(fn (Origin $origin): string => $origin === Origin::Module && $group->modules() !== []
                ? $origin->label().' '.implode(', ', $group->modules())
                : $origin->label())
            ->implode(' + ');
    }

    /**
     * Lists the files behind the selected group, relative to the project root.
     */
    private function selectedGroupDescription(): ?string
    {
        $key = $this->tableFilters['group']['value'] ?? null;
        $group = $key ? $this->inspector()->group($key) : null;

        if ($group === null) {
            return null;
        }

        if ($group->files === []) {
            return __('config-viewer::config-viewer.no_file');
        }

        $files = array_map(fn (ConfigFile $file): string => str_replace(base_path().DIRECTORY_SEPARATOR, '', $file->path), $group->files);

        return __('config-viewer::config-viewer.files', ['files' => implode(', ', $files)]);
    }

    private function inspector(): ConfigInspector
    {
        return app(ConfigInspector::class);
    }
}
