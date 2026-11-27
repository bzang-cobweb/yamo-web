<?php

namespace Bza\MigrationViewer\Filament\Pages;

use BackedEnum;
use Bza\MigrationViewer\Data\ChangeDetail;
use Bza\MigrationViewer\Data\MigrationQuery;
use Bza\MigrationViewer\Data\MigrationReplay;
use Bza\MigrationViewer\Data\TableChange;
use Bza\MigrationViewer\Data\TableSummary;
use Bza\MigrationViewer\Filament\MigrationViewerPlugin;
use Bza\MigrationViewer\Filament\Resources\MigrationTableResource;
use Bza\MigrationViewer\MigrationInspector;
use Bza\MigrationViewer\Operation;
use Bza\MigrationViewer\Origin;
use Filament\Pages\Page;
use Filament\Panel;
use Filament\Schemas\Components\EmbeddedTable;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontFamily;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Grouping\Group;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Pagination\LengthAwarePaginator;
use UnitEnum;

/**
 * Read-only list of the migration files, grouped by table. All the logic lives
 * in the core (MigrationInspector): this page only maps changes to table rows.
 */
class MigrationViewerPage extends Page implements HasTable
{
    /** The group key of the migrations that touch no table. */
    private const NO_TABLE = '__none__';

    use InteractsWithTable {
        updatedTableFilters as baseUpdatedTableFilters;
    }

    public static function canAccess(): bool
    {
        return MigrationViewerPlugin::get()->isAuthorized();
    }

    public static function getSlug(?Panel $panel = null): string
    {
        return MigrationViewerPlugin::get()->getSlug();
    }

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return MigrationViewerPlugin::get()->getNavigationGroup();
    }

    public static function getNavigationIcon(): string|BackedEnum|Htmlable|null
    {
        return MigrationViewerPlugin::get()->getNavigationIcon();
    }

    public static function getNavigationSort(): ?int
    {
        return MigrationViewerPlugin::get()->getNavigationSort();
    }

    public static function getNavigationLabel(): string
    {
        return MigrationViewerPlugin::get()->getNavigationLabel();
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
            ->records(fn (?string $search, ?array $filters, int|string $page, int|string $recordsPerPage): LengthAwarePaginator => $this->records($search, $filters ?? [], (int) $page, $recordsPerPage))
            ->columns([
                TextColumn::make('migration')
                    ->label(__('migration-viewer::migration-viewer.columns.migration'))
                    ->fontFamily(FontFamily::Mono)
                    ->description(fn (array $record): string => $record['path'])
                    ->searchable()
                    ->copyable()
                    ->wrap(),
                TextColumn::make('operation')
                    ->label(__('migration-viewer::migration-viewer.columns.operation'))
                    ->formatStateUsing(fn (string $state): string => Operation::from($state)->label())
                    ->color(fn (string $state): string => Operation::from($state)->color())
                    ->badge(),
                TextColumn::make('details')
                    ->label(__('migration-viewer::migration-viewer.columns.details'))
                    ->fontFamily(FontFamily::Mono)
                    ->listWithLineBreaks()
                    ->limitList(6)
                    ->expandableLimitedList()
                    ->wrap(),
                TextColumn::make('origin')
                    ->label(__('migration-viewer::migration-viewer.columns.origin'))
                    ->badge(),
                TextColumn::make('status')
                    ->label(__('migration-viewer::migration-viewer.columns.status'))
                    ->formatStateUsing(fn (string $state): string => __('migration-viewer::migration-viewer.statuses.'.$state))
                    ->color(fn (string $state): string => $this->statusColor($state))
                    ->badge(),
            ])
            ->groups([
                Group::make('group')
                    ->label(__('migration-viewer::migration-viewer.columns.table'))
                    ->getTitleFromRecordUsing(fn (array $record): string => $record['table'] ?? __('migration-viewer::migration-viewer.no_table'))
                    ->getDescriptionFromRecordUsing(fn (array $record): ?string => $this->tableDescription($record['table'])),
            ])
            ->defaultGroup('group')
            ->groupingSettingsHidden()
            // A click on a row opens its table's detail page (migration_tables). Rows touching no table have none.
            ->recordUrl(fn (array $record): ?string => $record['table'] !== null
                ? MigrationTableResource::getUrl('view', ['record' => $record['table']])
                : null)
            ->filters([
                SelectFilter::make('table')
                    ->label(__('migration-viewer::migration-viewer.filters.table'))
                    ->options(fn (): array => array_combine(array_keys($this->inspector()->tables()), array_keys($this->inspector()->tables())))
                    ->searchable(),
                SelectFilter::make('origin')
                    ->label(__('migration-viewer::migration-viewer.filters.origin'))
                    ->options(fn (): array => collect(Origin::cases())->mapWithKeys(fn (Origin $origin): array => [$origin->value => $origin->label()])->all()),
                SelectFilter::make('module')
                    ->label(__('migration-viewer::migration-viewer.filters.module'))
                    ->options(fn (): array => array_combine($this->inspector()->modules(), $this->inspector()->modules()))
                    ->searchable(),
                SelectFilter::make('operation')
                    ->label(__('migration-viewer::migration-viewer.filters.operation'))
                    ->options(fn (): array => collect(Operation::cases())->mapWithKeys(fn (Operation $operation): array => [$operation->value => $operation->label()])->all()),
                SelectFilter::make('status')
                    ->label(__('migration-viewer::migration-viewer.filters.status'))
                    ->options([
                        'ran' => __('migration-viewer::migration-viewer.statuses.ran'),
                        'pending' => __('migration-viewer::migration-viewer.statuses.pending'),
                    ]),
            ], layout: FiltersLayout::AboveContent)
            // The module field is hidden here rather than with the filter's visible(): the filters form is built
            // before the new origin is applied, so a hidden filter would only appear on the next request.
            ->filtersFormSchema(function (array $filters): array {
                $filters['module']->visible(fn (): bool => $this->isModuleOriginSelected($this->tableFilters ?? []) && $this->inspector()->modules() !== []);

                return array_values($filters);
            })
            ->deferFilters(false)
            ->description(fn (): ?string => $this->failuresDescription())
            ->paginated([25, 50, 100, 'all'])
            ->defaultPaginationPageOption(100)
            ->emptyStateHeading(__('migration-viewer::migration-viewer.empty'));
    }

    private function statusColor(string $status): string
    {
        return match ($status) {
            'ran' => 'success',
            'pending' => 'warning',
            default => 'gray',
        };
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function records(?string $search, array $filters, int $page, int|string $perPage): LengthAwarePaginator
    {
        $status = $filters['status']['value'] ?? null;

        $changes = $this->inspector()->changes(new MigrationQuery(
            table: ($filters['table']['value'] ?? null) ?: null,
            origin: Origin::tryFrom((string) ($filters['origin']['value'] ?? '')),
            // A hidden filter is ignored: the module only counts with the "Module" origin.
            module: $this->isModuleOriginSelected($filters) ? (($filters['module']['value'] ?? null) ?: null) : null,
            operation: Operation::tryFrom((string) ($filters['operation']['value'] ?? '')),
            ran: $status ? $status === 'ran' : null,
            search: $search,
        ));

        $total = count($changes);
        $perPage = $perPage === 'all' ? max($total, 1) : (int) $perPage;

        $rows = [];

        foreach (array_slice($changes, ($page - 1) * $perPage, $perPage) as $change) {
            $rows[$change->key()] = $this->row($change);
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
     * @param  array<string, mixed>  $filters
     */
    private function isModuleOriginSelected(array $filters): bool
    {
        return ($filters['origin']['value'] ?? null) === Origin::Module->value;
    }

    /**
     * @return array<string, mixed>
     */
    private function row(TableChange $change): array
    {
        $details = array_map(fn (ChangeDetail $detail): string => (string) $detail, $change->details);

        if ($change->renamedTo !== null && $details === []) {
            $details = ['→ '.$change->renamedTo];
        }

        return [
            '__key' => $change->key(),
            'group' => $change->table ?? self::NO_TABLE,
            'table' => $change->table,
            'migration' => $change->migration->name,
            'path' => str_replace(base_path().DIRECTORY_SEPARATOR, '', $change->migration->path),
            'operation' => $change->operation->value,
            'details' => $details,
            'origin' => $change->migration->module !== null
                ? $change->migration->origin->label().' '.$change->migration->module
                : $change->migration->origin->label(),
            'status' => match ($change->ran) {
                true => 'ran',
                false => 'pending',
                null => 'unknown',
            },
        ];
    }

    /**
     * "5 colonnes : id, name, email…" or "Supprimée", under the table's name.
     */
    private function tableDescription(?string $table): ?string
    {
        $summary = $table !== null ? $this->inspector()->table($table) : null;

        if (! $summary instanceof TableSummary) {
            return null;
        }

        if ($summary->dropped) {
            return __('migration-viewer::migration-viewer.dropped');
        }

        return trans_choice('migration-viewer::migration-viewer.columns_summary', count($summary->columns), [
            'count' => count($summary->columns),
            'columns' => implode(', ', array_keys($summary->columns)),
        ]);
    }

    /**
     * Warns about the migrations that could not be replayed to the end.
     */
    private function failuresDescription(): ?string
    {
        $failures = $this->inspector()->failures();

        if ($failures === []) {
            return null;
        }

        return __('migration-viewer::migration-viewer.failures', [
            'migrations' => implode(', ', array_map(fn (MigrationReplay $replay): string => $replay->migration->name, $failures)),
        ]);
    }

    private function inspector(): MigrationInspector
    {
        return app(MigrationInspector::class);
    }
}
