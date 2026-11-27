<?php

namespace Bza\MediaManager\Filament\Pages;

use BackedEnum;
use Bza\MediaManager\Ability;
use Bza\MediaManager\Filament\MediaManagerPlugin;
use Bza\MediaManager\Grantee;
use Bza\MediaManager\Grants;
use Bza\MediaManager\LinkedMedia;
use Bza\MediaManager\MediaAccess;
use Bza\MediaManager\MediaLibrary;
use Bza\MediaManager\MediaManagerException;
use Bza\MediaManager\Models\FolderGrant;
use Bza\MediaManager\Models\MediaFolder;
use Bza\MediaManager\Target;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Facades\Filament;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Panel;
use Filament\Schemas\Components\EmbeddedTable;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Js;
use Illuminate\Support\Number;
use Illuminate\Support\Str;
use Livewire\Attributes\Url;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;
use UnitEnum;

/**
 * The media manager, browsed like a file system: one table per location
 * (the root, a folder, the media of a model type, one of its collections),
 * folders first, then files. All the rules live in the core (MediaLibrary,
 * MediaAccess, Grants, LinkedMedia): this page maps them to rows and actions.
 */
class MediaManagerPage extends Page implements HasTable
{
    use InteractsWithTable;

    #[Url]
    public ?int $folder = null;

    /** The model type of the linked media being browsed. */
    #[Url]
    public ?string $linked = null;

    /** The collection of the linked media being browsed. */
    #[Url]
    public ?string $collection = null;

    public static function canAccess(): bool
    {
        return MediaManagerPlugin::get()->isAuthorized();
    }

    public static function getSlug(?Panel $panel = null): string
    {
        return MediaManagerPlugin::get()->getSlug();
    }

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return MediaManagerPlugin::get()->getNavigationGroup();
    }

    public static function getNavigationIcon(): string|BackedEnum|Htmlable|null
    {
        return MediaManagerPlugin::get()->getNavigationIcon();
    }

    public static function getNavigationSort(): ?int
    {
        return MediaManagerPlugin::get()->getNavigationSort();
    }

    public static function getNavigationLabel(): string
    {
        return MediaManagerPlugin::get()->getNavigationLabel();
    }

    public function mount(): void
    {
        if ($this->linked !== null) {
            abort_unless(array_key_exists($this->linked, $this->linkedMedia()->types()), 404);
            abort_unless($this->access()->allows(auth()->user(), Ability::View, Target::linked($this->linked)), 403);

            return;
        }

        if ($this->folder !== null) {
            abort_unless($this->access()->canBrowse(auth()->user(), $this->currentFolder()), 403);
        }
    }

    public function getTitle(): string|Htmlable
    {
        return match (true) {
            $this->linked !== null && $this->collection !== null => $this->collectionLabel($this->collection),
            $this->linked !== null => $this->linkedLabel($this->linked),
            $this->folder !== null => $this->currentFolder()->name,
            default => static::getNavigationLabel(),
        };
    }

    /**
     * @return array<string, string>
     */
    public function getBreadcrumbs(): array
    {
        $crumbs = [static::getUrl() => __('media-manager::media-manager.root')];

        if ($this->linked !== null) {
            $crumbs[static::getUrl(['linked' => $this->linked])] = $this->linkedLabel($this->linked);

            if ($this->collection !== null) {
                $crumbs[static::getUrl(['linked' => $this->linked, 'collection' => $this->collection])] = $this->collectionLabel($this->collection);
            }

            return $crumbs;
        }

        foreach ($this->currentFolder()?->ancestry() ?? [] as $folder) {
            $crumbs[static::getUrl(['folder' => $folder->getKey()])] = $folder->name;
        }

        return $crumbs;
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([EmbeddedTable::make()]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->records(fn (?string $search, int|string $page, int|string $recordsPerPage): LengthAwarePaginator => $this->records($search, (int) $page, $recordsPerPage))
            ->columns([
                ImageColumn::make('thumbnail')
                    ->label(__('media-manager::media-manager.columns.preview'))
                    ->imageSize(40)
                    ->square(),
                TextColumn::make('name')
                    ->label(__('media-manager::media-manager.columns.name'))
                    ->icon(fn (array $record): string => $record['icon'])
                    ->description(fn (array $record): ?string => $record['description'])
                    ->searchable()
                    ->wrap(),
                TextColumn::make('type')
                    ->label(__('media-manager::media-manager.columns.type'))
                    ->badge()
                    ->color(fn (array $record): string => $record['read_only'] ? 'gray' : 'primary'),
                TextColumn::make('size')
                    ->label(__('media-manager::media-manager.columns.size'))
                    ->formatStateUsing(fn (?int $state): string => $state === null ? '' : Number::fileSize($state)),
                TextColumn::make('updated_at')
                    ->label(__('media-manager::media-manager.columns.updated_at'))
                    ->dateTime(),
            ])
            ->recordUrl(fn (array $record): ?string => $record['url'])
            ->recordActions([
                ActionGroup::make([
                    $this->previewAction(),
                    $this->downloadAction(),
                    $this->copyUrlAction(),
                    $this->renameAction(),
                    $this->moveAction(),
                    $this->permissionsAction('permissions'),
                    $this->deleteAction(),
                ]),
            ])
            ->emptyStateHeading(__('media-manager::media-manager.empty'))
            ->paginated([25, 50, 100]);
    }

    /**
     * @return array<Action>
     */
    protected function getHeaderActions(): array
    {
        return [
            Action::make('newFolder')
                ->label(__('media-manager::media-manager.actions.new_folder'))
                ->icon('heroicon-o-folder-plus')
                ->visible(fn (): bool => $this->linked === null && $this->allowsHere(Ability::Create))
                ->schema([$this->nameInput()])
                ->action(function (array $data, Action $action): void {
                    $this->authorizeHere(Ability::Create);
                    $this->attempt($action, fn () => $this->library()->createFolder($this->currentFolder(), $data['name']));
                    $this->notify('folder_created');
                }),
            Action::make('upload')
                ->label(__('media-manager::media-manager.actions.upload'))
                ->icon('heroicon-o-arrow-up-tray')
                ->visible(fn (): bool => $this->linked === null && $this->folder !== null && $this->allowsHere(Ability::Create))
                ->schema([
                    FileUpload::make('files')
                        ->label(__('media-manager::media-manager.fields.files'))
                        ->multiple()
                        ->required()
                        ->storeFiles(false)
                        ->preserveFilenames()
                        ->maxSize((int) ceil($this->library()->maxFileSize() / 1024))
                        // Only when configured: the rule is added as soon as the method is called.
                        ->when(filled(config('media-manager.accepted_file_types')), fn (FileUpload $upload): FileUpload => $upload->acceptedFileTypes(config('media-manager.accepted_file_types'))),
                ])
                ->action(function (array $data, Action $action): void {
                    $this->authorizeHere(Ability::Create);
                    $files = array_values(array_filter((array) $data['files'], fn (mixed $file): bool => $file instanceof TemporaryUploadedFile));

                    foreach ($files as $file) {
                        $this->attempt($action, fn () => $this->library()->upload($this->currentFolder(), $file->getRealPath(), $file->getClientOriginalName()));
                    }

                    Notification::make()->success()->title(trans_choice('media-manager::media-manager.notifications.uploaded', count($files), ['count' => count($files)]))->send();
                }),
            $this->permissionsAction('currentPermissions', current: true),
        ];
    }

    private function previewAction(): Action
    {
        return Action::make('preview')
            ->label(__('media-manager::media-manager.actions.preview'))
            ->icon('heroicon-o-eye')
            ->visible(fn (array $record): bool => $record['kind'] === 'file' && $record['thumbnail'] !== null)
            ->modalHeading(fn (array $record): string => $record['name'])
            ->modalContent(fn (array $record): HtmlString => new HtmlString('<img src="'.e($record['thumbnail']).'" alt="'.e($record['name']).'" style="max-width:100%;max-height:70vh;margin:auto">'))
            ->modalSubmitAction(false)
            ->modalWidth(Width::FiveExtraLarge);
    }

    private function downloadAction(): Action
    {
        return Action::make('download')
            ->label(__('media-manager::media-manager.actions.download'))
            ->icon('heroicon-o-arrow-down-tray')
            ->visible(fn (array $record): bool => $record['kind'] === 'file' && $this->allowsOnFileOf($record, Ability::Download))
            ->action(function (array $record): StreamedResponse {
                $media = $this->media($record, Ability::Download);

                return response()->streamDownload(function () use ($media): void {
                    $stream = $media->stream();
                    fpassthru($stream);
                    fclose($stream);
                }, $media->file_name, ['Content-Type' => $media->mime_type ?? 'application/octet-stream']);
            });
    }

    private function copyUrlAction(): Action
    {
        return Action::make('copyUrl')
            ->label(__('media-manager::media-manager.actions.copy_url'))
            ->icon('heroicon-o-link')
            ->visible(fn (array $record): bool => $record['kind'] === 'file' && $record['public_url'] !== null && $this->allowsOnFileOf($record, Ability::Download))
            // The address is built by the server, never typed by a user.
            ->alpineClickHandler(fn (array $record): string => 'window.navigator.clipboard.writeText('.Js::from($record['public_url']).'); $tooltip('.Js::from(__('media-manager::media-manager.actions.copied')).', { theme: $store.theme, timeout: 2000 })');
    }

    private function renameAction(): Action
    {
        return Action::make('rename')
            ->label(__('media-manager::media-manager.actions.rename'))
            ->icon('heroicon-o-pencil-square')
            ->visible(fn (array $record): bool => $this->canChange($record, Ability::Update))
            ->fillForm(fn (array $record): array => ['name' => $record['kind'] === 'file' ? pathinfo($record['name'], PATHINFO_FILENAME) : $record['name']])
            ->schema([$this->nameInput()])
            ->action(function (array $record, array $data, Action $action): void {
                $this->attempt($action, fn () => $record['kind'] === 'folder'
                    ? $this->library()->renameFolder($this->folderRecord($record, Ability::Update), $data['name'])
                    : $this->library()->renameFile($this->media($record, Ability::Update), $data['name']));
                $this->notify('renamed');
            });
    }

    private function moveAction(): Action
    {
        return Action::make('move')
            ->label(__('media-manager::media-manager.actions.move'))
            ->icon('heroicon-o-arrows-right-left')
            ->visible(fn (array $record): bool => $this->canChange($record, Ability::Update))
            ->schema(fn (array $record): array => [
                Select::make('destination')
                    ->label(__('media-manager::media-manager.fields.destination'))
                    ->options($this->destinations($record))
                    ->searchable()
                    ->required(),
            ])
            ->action(function (array $record, array $data, Action $action): void {
                $to = $data['destination'] === 'root' ? null : MediaFolder::query()->findOrFail((int) $data['destination']);
                $this->access()->authorize(auth()->user(), Ability::Create, Target::folder($to));

                $this->attempt($action, function () use ($record, $to): void {
                    if ($record['kind'] === 'folder') {
                        $this->library()->moveFolder($this->folderRecord($record, Ability::Update), $to);

                        return;
                    }

                    // A file needs a folder: the root holds folders only.
                    if ($to === null) {
                        throw MediaManagerException::because('forbidden');
                    }

                    $this->library()->moveFile($this->media($record, Ability::Update), $to);
                });
                $this->notify('moved');
            });
    }

    private function deleteAction(): Action
    {
        return Action::make('delete')
            ->label(__('media-manager::media-manager.actions.delete'))
            ->icon('heroicon-o-trash')
            ->color('danger')
            ->visible(fn (array $record): bool => $this->canChange($record, Ability::Delete))
            ->requiresConfirmation()
            ->modalDescription(fn (array $record): string => __('media-manager::media-manager.confirm.'.($record['kind'] === 'folder' ? 'delete_folder' : 'delete_file'), ['name' => $record['name']]))
            ->action(function (array $record, Action $action): void {
                $this->attempt($action, fn () => $record['kind'] === 'folder'
                    ? $this->library()->deleteFolder($this->folderRecord($record, Ability::Delete))
                    : $this->library()->deleteFile($this->media($record, Ability::Delete)));
                $this->notify('deleted');
            });
    }

    /**
     * The rights of a row's folder (record action), or of the current
     * location (header action).
     */
    private function permissionsAction(string $name, bool $current = false): Action
    {
        $target = fn (?array $record): ?Target => $current ? $this->currentTarget() : $this->rowTarget($record ?? []);

        return Action::make($name)
            ->label(__('media-manager::media-manager.actions.'.($current && $this->folder === null && $this->linked === null ? 'root_permissions' : 'permissions')))
            ->icon('heroicon-o-key')
            ->color($current ? 'gray' : null)
            ->visible(function (?array $record = null) use ($target): bool {
                $on = $target($record);

                return $on !== null && $this->access()->allows(auth()->user(), Ability::Manage, $on);
            })
            ->modalWidth(Width::FiveExtraLarge)
            ->modalDescription(fn (?array $record = null): string => __('media-manager::media-manager.help.'.($target($record)?->isLinked() ? 'linked_permissions' : 'permissions')))
            ->fillForm(fn (?array $record = null): array => ['grants' => $this->grants()->on($target($record))->map(fn (FolderGrant $grant): array => [
                'grantee' => $grant->granteeKey(),
                ...collect(Ability::cases())->mapWithKeys(fn (Ability $ability): array => [$ability->value => match ($grant->value($ability)) {
                    true => 'allow',
                    false => 'deny',
                    null => 'inherit',
                }])->all(),
            ])->all()])
            ->schema(fn (?array $record = null): array => [
                Repeater::make('grants')
                    ->label(__('media-manager::media-manager.fields.grants'))
                    ->addActionLabel(__('media-manager::media-manager.fields.add_grant'))
                    ->defaultItems(0)
                    ->schema([
                        Select::make('grantee')
                            ->label(__('media-manager::media-manager.fields.grantee'))
                            ->options(fn (): array => $this->granteeOptions())
                            ->searchable()
                            ->required(),
                        Grid::make(3)->schema(array_map(
                            fn (Ability $ability): ToggleButtons => ToggleButtons::make($ability->value)
                                ->label($ability->label())
                                ->options($this->stateOptions())
                                ->colors(['inherit' => 'gray', 'allow' => 'success', 'deny' => 'danger'])
                                ->default('inherit')
                                ->inline()
                                ->grouped(),
                            $target($record)?->isLinked() ? Ability::forLinkedMedia() : Ability::cases(),
                        )),
                    ]),
            ])
            ->action(function (array $data, ?array $record = null) use ($target): void {
                $on = $target($record);
                $this->access()->authorize(auth()->user(), Ability::Manage, $on);

                $this->grants()->replace($on, array_values(array_map(fn (array $row): array => [
                    'grantee' => Grantee::fromKey((string) $row['grantee']),
                    'abilities' => collect(Ability::cases())->mapWithKeys(fn (Ability $ability): array => [$ability->value => match ($row[$ability->value] ?? 'inherit') {
                        'allow' => true,
                        'deny' => false,
                        default => null,
                    }])->all(),
                ], $data['grants'] ?? [])));

                $this->notify('permissions_saved');
            });
    }

    /**
     * @return array<string, string>
     */
    private function stateOptions(): array
    {
        return collect(['inherit', 'allow', 'deny'])->mapWithKeys(fn (string $state): array => [$state => __('media-manager::media-manager.states.'.$state)])->all();
    }

    private function nameInput(): TextInput
    {
        return TextInput::make('name')
            ->label(__('media-manager::media-manager.fields.name'))
            ->required()
            ->maxLength(200)
            ->autofocus();
    }

    /**
     * The rows of the current location, folders first, then files.
     */
    private function records(?string $search, int $page, int|string $perPage): LengthAwarePaginator
    {
        [$folders, $files] = $this->location();

        if (filled($search)) {
            $folders = array_values(array_filter($folders, fn (array $row): bool => Str::contains($row['name'], $search, ignoreCase: true)));
            $files?->where(fn (Builder $query) => $query->where('file_name', 'like', '%'.$search.'%')->orWhere('name', 'like', '%'.$search.'%'));
        }

        $total = count($folders) + ($files?->count() ?? 0);
        $perPage = $perPage === 'all' ? max($total, 1) : (int) $perPage;
        $offset = ($page - 1) * $perPage;

        $rows = array_slice($folders, $offset, $perPage);
        $left = $perPage - count($rows);

        if ($files !== null && $left > 0) {
            $media = $files->with('model')->orderBy('file_name')->offset(max(0, $offset - count($folders)))->limit($left)->get();
            $rows = [...$rows, ...$media->map(fn (Media $media): array => $this->fileRow($media))->all()];
        }

        return new LengthAwarePaginator(collect($rows)->keyBy('__key')->all(), $total, $perPage, $page);
    }

    /**
     * @return array{0: list<array<string, mixed>>, 1: Builder<Media>|null}
     */
    private function location(): array
    {
        $user = auth()->user();

        if ($this->linked !== null) {
            if ($this->collection !== null) {
                return [[], $this->linkedMedia()->query($this->linked, $this->collection)];
            }

            $rows = [];

            foreach ($this->linkedMedia()->collections($this->linked) as $collection => $count) {
                $rows[] = $this->row('collection:'.$collection, 'collection', $this->collectionLabel($collection), [
                    'icon' => 'heroicon-o-folder',
                    'description' => trans_choice('media-manager::media-manager.files_count', $count, ['count' => $count]),
                    'type' => __('media-manager::media-manager.read_only'),
                    'read_only' => true,
                    'url' => static::getUrl(['linked' => $this->linked, 'collection' => $collection]),
                ]);
            }

            return [$rows, null];
        }

        $folder = $this->currentFolder();
        $rows = [];

        foreach ($this->library()->folders($folder) as $child) {
            if ($this->access()->canBrowse($user, $child)) {
                $rows[] = $this->row('folder:'.$child->getKey(), 'folder', $child->name, [
                    'id' => $child->getKey(),
                    'icon' => 'heroicon-o-folder',
                    'type' => __('media-manager::media-manager.kinds.folder'),
                    'updated_at' => $child->updated_at,
                    'url' => static::getUrl(['folder' => $child->getKey()]),
                ]);
            }
        }

        if ($folder === null) {
            foreach ($this->linkedMedia()->types() as $type => $count) {
                if ($this->access()->allows($user, Ability::View, Target::linked($type))) {
                    $rows[] = $this->row('linked:'.$type, 'linked', $this->linkedLabel($type), [
                        'linked_type' => $type,
                        'icon' => 'heroicon-o-lock-closed',
                        'description' => trans_choice('media-manager::media-manager.files_count', $count, ['count' => $count]),
                        'type' => __('media-manager::media-manager.kinds.linked'),
                        'read_only' => true,
                        'url' => static::getUrl(['linked' => $type]),
                    ]);
                }
            }

            return [$rows, null];
        }

        $files = $this->access()->allowsFolder($user, Ability::View, $folder) ? $this->library()->files($folder) : null;

        return [$rows, $files];
    }

    /**
     * @param  array<string, mixed>  $values
     * @return array<string, mixed>
     */
    private function row(string $key, string $kind, string $name, array $values = []): array
    {
        return [
            '__key' => $key,
            'kind' => $kind,
            'id' => null,
            'linked_type' => null,
            'name' => $name,
            'icon' => 'heroicon-o-document',
            'description' => null,
            'thumbnail' => null,
            'public_url' => null,
            'type' => null,
            'size' => null,
            'updated_at' => null,
            'read_only' => false,
            'url' => null,
            ...$values,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function fileRow(Media $media): array
    {
        $managed = $this->library()->isManaged($media);
        $url = $this->publicUrl($media);

        return $this->row('file:'.$media->getKey(), 'file', $media->file_name, [
            'id' => $media->getKey(),
            'linked_type' => $managed ? null : $media->model_type,
            'icon' => $this->fileIcon($media),
            'description' => $managed ? null : __('media-manager::media-manager.attached_to', ['owner' => $this->ownerLabel($media)]),
            'thumbnail' => Str::startsWith((string) $media->mime_type, 'image/') ? $url : null,
            'public_url' => $url,
            'type' => Str::upper(pathinfo($media->file_name, PATHINFO_EXTENSION)) ?: $media->mime_type,
            'size' => (int) $media->size,
            'updated_at' => $media->updated_at,
            'read_only' => ! $managed,
        ]);
    }

    private function fileIcon(Media $media): string
    {
        return match (true) {
            Str::startsWith((string) $media->mime_type, 'image/') => 'heroicon-o-photo',
            Str::startsWith((string) $media->mime_type, 'video/') => 'heroicon-o-film',
            Str::startsWith((string) $media->mime_type, 'audio/') => 'heroicon-o-musical-note',
            default => 'heroicon-o-document',
        };
    }

    private function publicUrl(Media $media): ?string
    {
        try {
            return $media->getUrl();
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * Where a row may be moved: folders where the user may add, never the
     * folder itself nor its subfolders; the root for folders only.
     *
     * @param  array<string, mixed>  $record
     * @return array<string, string>
     */
    private function destinations(array $record): array
    {
        $user = auth()->user();
        $options = [];

        if ($record['kind'] === 'folder' && $this->access()->allows($user, Ability::Create, Target::root())) {
            $options['root'] = __('media-manager::media-manager.root');
        }

        $folders = MediaFolder::query()->orderBy('name')->get()->keyBy('id');

        foreach ($folders as $folder) {
            $path = [];
            $current = $folder;
            $excluded = false;

            while ($current !== null) {
                if ($record['kind'] === 'folder' && $current->getKey() === $record['id']) {
                    $excluded = true;
                }

                array_unshift($path, $current->name);
                $current = $current->parent_id !== null ? $folders->get($current->parent_id) : null;
            }

            if (! $excluded && $this->access()->allowsFolder($user, Ability::Create, $folder)) {
                $options[(string) $folder->getKey()] = implode(' / ', $path);
            }
        }

        asort($options);

        return $options;
    }

    /**
     * @return array<string, array<string, string>>
     */
    private function granteeOptions(): array
    {
        $options = [];

        foreach ($this->grants()->granteeModels() as $model => $attribute) {
            $group = $this->modelLabel($model);

            foreach ($model::query()->orderBy($attribute)->limit(500)->get() as $grantee) {
                $options[$group][Grantee::of($grantee)->key()] = (string) $grantee->getAttribute($attribute);
            }
        }

        return $options;
    }

    private function canChange(array $record, Ability $ability): bool
    {
        return match ($record['kind']) {
            'folder' => $this->access()->allows(auth()->user(), $ability, Target::folder($record['id'])),
            'file' => ! $record['read_only'] && $this->allowsOnFileOf($record, $ability),
            default => false,
        };
    }

    /**
     * Whether the ability is given on the folder a file row is listed in.
     *
     * @param  array<string, mixed>  $record
     */
    private function allowsOnFileOf(array $record, Ability $ability): bool
    {
        return $this->access()->allows(auth()->user(), $ability, $record['linked_type'] !== null ? Target::linked($record['linked_type']) : Target::folder($this->folder));
    }

    /**
     * @param  array<string, mixed>  $record
     */
    private function rowTarget(array $record): ?Target
    {
        return match ($record['kind'] ?? null) {
            'folder' => Target::folder($record['id']),
            'linked' => Target::linked($record['linked_type']),
            default => null,
        };
    }

    private function currentTarget(): Target
    {
        return $this->linked !== null ? Target::linked($this->linked) : Target::folder($this->folder);
    }

    private function allowsHere(Ability $ability): bool
    {
        return $this->access()->allows(auth()->user(), $ability, $this->currentTarget());
    }

    private function authorizeHere(Ability $ability): void
    {
        $this->access()->authorize(auth()->user(), $ability, $this->currentTarget());
    }

    /**
     * The folder of a row, once the ability is checked on it.
     *
     * @param  array<string, mixed>  $record
     */
    private function folderRecord(array $record, Ability $ability): MediaFolder
    {
        $folder = MediaFolder::query()->findOrFail($record['id']);
        $this->access()->authorize(auth()->user(), $ability, Target::folder($folder));

        return $folder;
    }

    /**
     * The media of a row, once the ability is checked where it is: its
     * folder, or the linked media of its model type.
     *
     * @param  array<string, mixed>  $record
     */
    private function media(array $record, Ability $ability): Media
    {
        $model = config('media-library.media_model', Media::class);
        /** @var Media $media */
        $media = $model::query()->findOrFail($record['id']);

        $target = $this->library()->isManaged($media) ? Target::folder((int) $media->model_id) : Target::linked($media->model_type);
        $this->access()->authorize(auth()->user(), $ability, $target);

        return $media;
    }

    /**
     * Runs an operation; a refusal is shown and keeps the modal open.
     */
    private function attempt(Action $action, callable $operation): void
    {
        try {
            $operation();
        } catch (MediaManagerException $exception) {
            Notification::make()->danger()->title($exception->getMessage())->send();
            $action->halt();
        }
    }

    private function notify(string $key): void
    {
        Notification::make()->success()->title(__('media-manager::media-manager.notifications.'.$key))->send();
    }

    private function currentFolder(): ?MediaFolder
    {
        if ($this->folder === null) {
            return null;
        }

        return once(fn (): MediaFolder => MediaFolder::query()->findOrFail($this->folder));
    }

    private function linkedLabel(string $type): string
    {
        return $this->modelLabel(Relation::getMorphedModel($type) ?? $type);
    }

    private function collectionLabel(string $collection): string
    {
        return Str::headline($collection);
    }

    /**
     * The plural label of the model's Filament resource, else its class name.
     */
    private function modelLabel(string $class): string
    {
        $resource = class_exists($class) ? Filament::getModelResource($class) : null;

        return $resource !== null ? Str::ucfirst($resource::getPluralModelLabel()) : Str::headline(class_basename($class));
    }

    private function ownerLabel(Media $media): string
    {
        $owner = $media->model;

        if (! $owner instanceof Model) {
            return '#'.$media->model_id;
        }

        $resource = Filament::getModelResource($owner);
        $title = $resource !== null ? $resource::getRecordTitle($owner) : null;

        return filled($title) ? strip_tags((string) $title) : class_basename($owner).' #'.$owner->getKey();
    }

    private function access(): MediaAccess
    {
        return app(MediaAccess::class);
    }

    private function library(): MediaLibrary
    {
        return app(MediaLibrary::class);
    }

    private function grants(): Grants
    {
        return app(Grants::class);
    }

    private function linkedMedia(): LinkedMedia
    {
        return app(LinkedMedia::class);
    }
}
