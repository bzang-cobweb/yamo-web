<?php

namespace Bza\MigrationViewer\Filament\Resources;

use BackedEnum;
use Bza\MigrationViewer\Filament\MigrationViewerPlugin;
use Bza\MigrationViewer\Filament\Resources\MigrationTableResource\Pages\ListMigrationTables;
use Bza\MigrationViewer\Filament\Resources\MigrationTableResource\Pages\ViewMigrationTable;
use Bza\MigrationViewer\Models\MigrationTable;
use Bza\MigrationViewer\Operation;
use Bza\MigrationViewer\Origin;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\RepeatableEntry\TableColumn;
use Filament\Infolists\Components\TextEntry;
use Filament\Panel;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontFamily;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Auth\Access\Response;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

/**
 * The tables stored in migration_tables: a list, then a detail page per table
 * (its state, its migration files, its current columns). Read-only.
 */
class MigrationTableResource extends Resource
{
    protected static ?string $model = MigrationTable::class;

    protected static ?string $recordTitleAttribute = 'name';

    public static function getSlug(?Panel $panel = null): string
    {
        return MigrationViewerPlugin::get()->getTablesSlug();
    }

    /**
     * Read-only, and only for the users the plugin authorizes: no policy needed.
     */
    public static function getAuthorizationResponse(string|UnitEnum $action, ?Model $record = null): Response
    {
        $action = $action instanceof UnitEnum ? $action->name : $action;

        return in_array($action, ['viewAny', 'view'], true) && MigrationViewerPlugin::get()->isAuthorized()
            ? Response::allow()
            : Response::deny();
    }

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return MigrationViewerPlugin::get()->getNavigationGroup();
    }

    public static function getNavigationIcon(): string|BackedEnum|null
    {
        return 'heroicon-o-table-cells';
    }

    public static function getNavigationSort(): ?int
    {
        $sort = MigrationViewerPlugin::get()->getNavigationSort();

        return $sort === null ? null : $sort + 1;
    }

    public static function getModelLabel(): string
    {
        return __('migration-viewer::migration-viewer.tables.model');
    }

    public static function getPluralModelLabel(): string
    {
        return __('migration-viewer::migration-viewer.tables.title');
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label(__('migration-viewer::migration-viewer.columns.table'))
                    ->fontFamily(FontFamily::Mono)
                    ->weight('medium')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('dropped')
                    ->label(__('migration-viewer::migration-viewer.detail.state'))
                    ->formatStateUsing(fn (bool $state): string => static::stateLabel($state))
                    ->color(fn (bool $state): string => $state ? 'danger' : 'success')
                    ->badge(),
                TextColumn::make('columns_count')
                    ->label(__('migration-viewer::migration-viewer.tables.columns_count'))
                    ->numeric()
                    ->sortable(),
                TextColumn::make('migrations_count')
                    ->label(__('migration-viewer::migration-viewer.tables.migrations_count'))
                    ->numeric()
                    ->sortable(),
                TextColumn::make('pending_count')
                    ->label(__('migration-viewer::migration-viewer.statuses.pending'))
                    ->color(fn (int $state): string => $state > 0 ? 'warning' : 'gray')
                    ->badge()
                    ->sortable(),
                TextColumn::make('last_migration')
                    ->label(__('migration-viewer::migration-viewer.tables.last_migration'))
                    ->fontFamily(FontFamily::Mono)
                    ->size('xs')
                    ->toggleable(),
                TextColumn::make('synced_at')
                    ->label(__('migration-viewer::migration-viewer.tables.synced_at'))
                    ->since()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TernaryFilter::make('dropped')
                    ->label(__('migration-viewer::migration-viewer.detail.dropped')),
                Filter::make('pending')
                    ->label(__('migration-viewer::migration-viewer.tables.with_pending'))
                    ->query(fn (Builder $query): Builder => $query->where('pending_count', '>', 0)),
            ])
            ->defaultSort('name')
            ->paginated([25, 50, 100, 'all'])
            ->defaultPaginationPageOption(50)
            ->emptyStateHeading(__('migration-viewer::migration-viewer.tables.empty'))
            ->emptyStateDescription(__('migration-viewer::migration-viewer.tables.empty_hint'));
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->columns(1)->components([
            Section::make()
                ->columns(4)
                ->schema([
                    TextEntry::make('name')
                        ->label(__('migration-viewer::migration-viewer.columns.table'))
                        ->fontFamily(FontFamily::Mono)
                        ->copyable(),
                    TextEntry::make('dropped')
                        ->label(__('migration-viewer::migration-viewer.detail.state'))
                        ->formatStateUsing(fn (bool $state): string => static::stateLabel($state))
                        ->color(fn (bool $state): string => $state ? 'danger' : 'success')
                        ->badge(),
                    TextEntry::make('pending_count')
                        ->label(__('migration-viewer::migration-viewer.statuses.pending'))
                        ->color(fn (int $state): string => $state > 0 ? 'warning' : 'gray')
                        ->badge(),
                    TextEntry::make('synced_at')
                        ->label(__('migration-viewer::migration-viewer.tables.synced_at'))
                        ->since()
                        ->dateTimeTooltip(),
                ]),
            Section::make(__('migration-viewer::migration-viewer.detail.migrations'))
                ->schema([
                    RepeatableEntry::make('migrations')
                        ->hiddenLabel()
                        ->table([
                            TableColumn::make(__('migration-viewer::migration-viewer.columns.migration')),
                            TableColumn::make(__('migration-viewer::migration-viewer.columns.operation')),
                            TableColumn::make(__('migration-viewer::migration-viewer.columns.details')),
                            TableColumn::make(__('migration-viewer::migration-viewer.columns.origin')),
                            TableColumn::make(__('migration-viewer::migration-viewer.columns.status')),
                        ])
                        ->schema([
                            TextEntry::make('migration')
                                ->fontFamily(FontFamily::Mono)
                                ->size('xs')
                                ->copyable(),
                            TextEntry::make('operation')
                                ->formatStateUsing(fn (string $state): string => Operation::from($state)->label())
                                ->color(fn (string $state): string => Operation::from($state)->color())
                                ->badge(),
                            TextEntry::make('details')
                                ->fontFamily(FontFamily::Mono)
                                ->size('xs')
                                ->listWithLineBreaks()
                                ->placeholder('—'),
                            TextEntry::make('origin')
                                ->formatStateUsing(fn (string $state): string => Origin::from($state)->label())
                                ->badge(),
                            TextEntry::make('status')
                                ->formatStateUsing(fn (string $state): string => __('migration-viewer::migration-viewer.statuses.'.$state))
                                ->color(fn (string $state): string => match ($state) {
                                    'ran' => 'success',
                                    'pending' => 'warning',
                                    default => 'gray',
                                })
                                ->badge(),
                        ]),
                ]),
            Section::make(__('migration-viewer::migration-viewer.detail.columns'))
                ->schema([
                    RepeatableEntry::make('columns')
                        ->hiddenLabel()
                        ->placeholder(__('migration-viewer::migration-viewer.detail.no_column'))
                        ->table([
                            TableColumn::make(__('migration-viewer::migration-viewer.detail.column')),
                            TableColumn::make(__('migration-viewer::migration-viewer.detail.definition')),
                        ])
                        ->schema([
                            TextEntry::make('name')->fontFamily(FontFamily::Mono)->weight('medium'),
                            TextEntry::make('definition')->fontFamily(FontFamily::Mono)->color('gray'),
                        ]),
                ]),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListMigrationTables::route('/'),
            'view' => ViewMigrationTable::route('/{record}'),
        ];
    }

    private static function stateLabel(bool $dropped): string
    {
        return $dropped ? __('migration-viewer::migration-viewer.detail.dropped') : __('migration-viewer::migration-viewer.detail.present');
    }
}
