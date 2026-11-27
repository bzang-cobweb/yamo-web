<?php

namespace Bza\MigrationViewer\Filament\Resources\MigrationTableResource\Pages;

use Bza\MigrationViewer\Filament\Concerns\GeneratesMigrations;
use Bza\MigrationViewer\Filament\Resources\MigrationTableResource;
use Bza\MigrationViewer\Filament\Schemas\NewColumnsRepeater;
use Bza\MigrationViewer\Generator\MigrationGenerator;
use Bza\MigrationViewer\Generator\NewColumn;
use Bza\MigrationViewer\Generator\NewTable;
use Closure;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Contracts\Support\Htmlable;

/**
 * « Nouvelle table »: its name, options and columns become one
 * create_<table>_table migration, written but not run.
 */
class CreateMigrationTable extends Page
{
    use GeneratesMigrations;

    private const KEY = 'migration-viewer::migration-viewer.generator.';

    protected static string $resource = MigrationTableResource::class;

    /** @var array<string, mixed>|null */
    public ?array $data = [];

    public static function canAccess(array $parameters = []): bool
    {
        return static::generatorEnabled();
    }

    public function mount(): void
    {
        $this->form->fill();
    }

    public function getTitle(): string|Htmlable
    {
        return __(self::KEY.'create_table');
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                Section::make()
                    ->description(fn (MigrationGenerator $generator): string => __(self::KEY.'directory', ['directory' => str_replace(base_path().DIRECTORY_SEPARATOR, '', $generator->directory())]))
                    ->schema([
                        TextInput::make('name')
                            ->label(__(self::KEY.'table_name'))
                            ->required()
                            ->regex(NewColumn::NAME_PATTERN)
                            ->validationMessages(['regex' => __(self::KEY.'name_format')])
                            ->rule(fn (): Closure => function (string $attribute, mixed $value, Closure $fail): void {
                                if (app(MigrationGenerator::class)->tableExists((string) $value)) {
                                    $fail(__(self::KEY.'table_exists'));
                                }
                            }),
                        Grid::make(3)->schema([
                            Toggle::make('id')->label(__(self::KEY.'with_id'))->default(true),
                            Toggle::make('timestamps')->label(__(self::KEY.'with_timestamps'))->default(true),
                            Toggle::make('soft_deletes')->label(__(self::KEY.'with_soft_deletes'))->default(false),
                        ]),
                    ]),
                NewColumnsRepeater::make(),
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
                        Action::make('cancel')->label(__('migration-viewer::migration-viewer.detail.cancel'))->color('gray')->url(MigrationTableResource::getUrl('index')),
                    ]),
                ]),
        ]);
    }

    public function save(MigrationGenerator $generator): void
    {
        $data = $this->form->getState();

        $written = $this->generateMigration(function () use ($data, $generator): string {
            return $generator->createTable(new NewTable(
                name: (string) $data['name'],
                columns: NewColumnsRepeater::columns($data['columns'] ?? []),
                id: (bool) ($data['id'] ?? true),
                timestamps: (bool) ($data['timestamps'] ?? true),
                softDeletes: (bool) ($data['soft_deletes'] ?? false),
            ));
        });

        if ($written) {
            $this->redirect(MigrationTableResource::getUrl('view', ['record' => $data['name']]));
        }
    }
}
