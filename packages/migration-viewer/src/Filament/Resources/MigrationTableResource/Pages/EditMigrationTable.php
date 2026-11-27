<?php

namespace Bza\MigrationViewer\Filament\Resources\MigrationTableResource\Pages;

use Bza\MigrationViewer\Data\MigrationFile;
use Bza\MigrationViewer\Filament\Concerns\GeneratesMigrations;
use Bza\MigrationViewer\Filament\Resources\MigrationTableResource;
use Bza\MigrationViewer\Filament\Schemas\NewColumnsRepeater;
use Bza\MigrationViewer\Generator\ColumnEdit;
use Bza\MigrationViewer\Generator\ColumnState;
use Bza\MigrationViewer\Generator\MigrationGenerator;
use Bza\MigrationViewer\Generator\NewColumn;
use Bza\MigrationViewer\MigrationInspector;
use Bza\MigrationViewer\Models\MigrationTable;
use Closure;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Resources\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\Artisan;
use Throwable;

/**
 * Edits a table: every change of the form (table renamed, columns added,
 * changed, renamed or removed) becomes one alter_<table>_table migration,
 * written but not run.
 *
 * @property MigrationTable $record
 */
class EditMigrationTable extends Page
{
    use GeneratesMigrations;
    use InteractsWithRecord;

    private const KEY = 'migration-viewer::migration-viewer.generator.';

    protected static string $resource = MigrationTableResource::class;

    /** @var array<string, mixed>|null */
    public ?array $data = [];

    public static function canAccess(array $parameters = []): bool
    {
        $record = $parameters['record'] ?? null;

        return static::generatorEnabled() && ! ($record instanceof MigrationTable && $record->dropped);
    }

    public function mount(int|string $record, MigrationGenerator $generator): void
    {
        $this->record = $this->resolveRecord($record);

        abort_unless(static::canAccess(['record' => $this->record]), 403);

        $this->form->fill([
            'name' => $this->record->name,
            'columns' => array_values(array_map(fn (ColumnState $state): array => $state->toFormItem(), $generator->currentColumns($this->record->name))),
        ]);
    }

    public function getTitle(): string|Htmlable
    {
        return __(self::KEY.'edit_table', ['table' => $this->record->name]);
    }

    protected function getHeaderActions(): array
    {
        return [
            // Only this table's pending files ("migrate --path=…"): the others stay pending.
            $this->migrateAction('migrateTable', 'migrate_table', 'migrate_table_confirm', 'warning', fn (): array => $this->tablePending()),
            // Every pending migration of the application ("migrate").
            $this->migrateAction('migrateAll', 'migrate_all', 'migrate_confirm', 'gray', fn (): array => app(MigrationInspector::class)->pending(), all: true),
        ];
    }

    /**
     * The pending migrations that touch this table, in run order.
     *
     * @return list<MigrationFile>
     */
    private function tablePending(): array
    {
        $files = [];

        foreach (app(MigrationInspector::class)->table($this->record->name)?->changes ?? [] as $change) {
            if ($change->ran === false) {
                $files[$change->migration->name] = $change->migration;
            }
        }

        return array_values($files);
    }

    /**
     * Runs pending migrations after a confirmation that lists them. Unsaved
     * changes of the form are not part of them: generate the migration first.
     *
     * @param  Closure(): list<MigrationFile>  $pending
     * @param  bool  $all  "migrate" as is; otherwise only the listed files, with --path.
     */
    private function migrateAction(string $name, string $label, string $confirm, string $color, Closure $pending, bool $all = false): Action
    {
        return Action::make($name)
            ->label(__(self::KEY.$label))
            ->icon(Heroicon::OutlinedPlay)
            ->color($color)
            ->disabled(fn (): bool => $pending() === [])
            ->tooltip(fn (): ?string => $pending() === [] ? __(self::KEY.'nothing_to_migrate') : null)
            ->requiresConfirmation()
            ->modalHeading(__(self::KEY.$label))
            ->modalDescription(fn (): string => trans_choice(self::KEY.$confirm, count($pending()), [
                'count' => count($pending()),
                'migrations' => implode(', ', array_map(fn (MigrationFile $file): string => $file->name, $pending())),
            ]))
            ->modalSubmitActionLabel(__(self::KEY.$label))
            ->action(function () use ($pending, $all): void {
                // The generator's directory, in case it was configured after the migrator was built.
                app('migrator')->path((string) config('migration-viewer.generator.path'));

                $options = ['--force' => true];

                if (! $all) {
                    $options['--path'] = array_map(fn (MigrationFile $file): string => $file->path, $pending());
                    $options['--realpath'] = true;
                }

                try {
                    Artisan::call('migrate', $options);
                } catch (Throwable $exception) {
                    Notification::make()
                        ->title(__(self::KEY.'migrate_failed'))
                        ->body($exception->getMessage())
                        ->danger()
                        ->persistent()
                        ->send();

                    return;
                }

                // migration_tables was synchronized by the "migrations ended" listener.
                Notification::make()
                    ->title(__(self::KEY.'migrated'))
                    ->body(trim(Artisan::output()) ?: null)
                    ->success()
                    ->send();

                // A pending rename may have moved the table: back to the list then.
                $this->redirect(MigrationTable::query()->where('name', $this->record->name)->exists()
                    ? MigrationTableResource::getUrl('edit', ['record' => $this->record->name])
                    : MigrationTableResource::getUrl('index'));
            });
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                Section::make()
                    ->description(fn (MigrationGenerator $generator): string => __(self::KEY.'edit_help', ['directory' => str_replace(base_path().DIRECTORY_SEPARATOR, '', $generator->directory())]))
                    ->schema([
                        TextInput::make('name')
                            ->label(__(self::KEY.'table_name'))
                            ->required()
                            ->regex(NewColumn::NAME_PATTERN)
                            ->validationMessages(['regex' => __(self::KEY.'name_format')])
                            ->rule(fn (): Closure => function (string $attribute, mixed $value, Closure $fail): void {
                                if ($value !== $this->record->name && app(MigrationGenerator::class)->tableExists((string) $value)) {
                                    $fail(__(self::KEY.'table_exists'));
                                }
                            }),
                    ]),
                NewColumnsRepeater::make(editing: true),
            ]);
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            Form::make([EmbeddedSchema::make('form')])
                ->livewireSubmitHandler('save')
                ->footer([
                    Actions::make([
                        Action::make('save')->label(__(self::KEY.'generate'))->submit('save'),
                        Action::make('cancel')->label(__('migration-viewer::migration-viewer.detail.cancel'))->color('gray')->url(MigrationTableResource::getUrl('view', ['record' => $this->record])),
                    ]),
                ]),
        ]);
    }

    public function save(MigrationGenerator $generator): void
    {
        $data = $this->form->getState();
        $name = (string) $data['name'];

        $written = $this->generateMigration(fn (): string => $generator->alterTable(
            $this->record->name,
            $name,
            array_values(array_map(fn (array $item): ColumnEdit => ColumnEdit::fromArray($item), $data['columns'] ?? [])),
        ));

        if ($written) {
            $this->redirect(MigrationTableResource::getUrl('view', ['record' => $name]));
        }
    }
}
