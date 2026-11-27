# bza/laravel-media-manager

A media manager browsed like a file system, built on [spatie/laravel-medialibrary](https://github.com/spatie/laravel-medialibrary).
A Filament adapter ships with it.

- Folders: create, rename, move, delete (empty folders only).
- Files: upload, rename, move, delete, download, preview images, copy their address.
- Rights per folder, given to roles or to users: view, download, add, edit, delete, manage rights.
- The media already attached to the application's models (photos, avatars…) are listed read-only, one folder per model type, then one per collection.

## How it works

### Folders and files

A folder is a row of `media_folders` (`parent_id`, `name`). It is a `HasMedia` model, so its files are ordinary media of its `files` collection.

Names are unique within a folder, whatever the case. An uploaded file whose name is taken gets a number: `plan-1.pdf`.

Files are stored under `media-manager/<uuid>/<file name>` by `FolderPathGenerator`. The generator is registered for the folders only (`media-library.custom_path_generators`), so the other models keep the application's generator. The path depends on neither the folder nor the name:

- moving a file only changes its `model_id`, so its address does not change;
- renaming a file changes its `file_name`, and the media library renames it on the disk.

The root holds folders only: a file always belongs to a folder.

### Rights

Rights are rows of `media_folder_grants`. A row gives one grantee (a role, a user, any model) its abilities on one target. The target is the root, a folder, or the read-only folder of one model type's media. Each ability is given (`true`), refused (`false`), or left out (inherited).

| Ability    | Allows |
|------------|--------|
| `view`     | Open the folder, see its files and preview them |
| `download` | Download the files, copy their address |
| `create`   | Upload files, create subfolders |
| `update`   | Rename and move the folder and its files |
| `delete`   | Delete the folder (when empty) and its files |
| `manage`   | Set the folder's rights |

How a right is decided:

- For each grantee of the user, the right comes from the nearest place that gives or refuses it. The search starts at the folder and goes up through its parents to the root. A subfolder therefore overrides its parents, for that ability only.
- The user may act when at least one of its grantees may. A grantee is the user itself or one of its roles.
- The read-only folders of the linked media inherit nothing, not even the root's rights. Only the rights given on them count, so giving rights at the root never exposes avatars.
- A folder the user cannot view is still listed when it leads to a viewable subfolder. Its own files stay hidden.
- The super user callback (super administrators, say) bypasses every right.

`GranteeResolver` lists the grantees of a user. The default resolver, `UserAndRolesGranteeResolver`, returns the user and, when it has a `roles` relation (spatie/laravel-permission), its roles.

### Linked media

The media of the other models are found in the `media` table, so no model has to be declared. They cannot be renamed, moved or deleted from the manager: they belong to their model, whose collections set their own rules.

## Installation

```bash
composer require bza/laravel-media-manager
php artisan migrate
```

Add the plugin to a Filament panel:

```php
use Bza\MediaManager\Filament\MediaManagerPlugin;

$panel->plugin(MediaManagerPlugin::make()
    ->navigationGroup('Content')
    ->navigationSort(50)
    ->superUser(fn ($user): bool => $user->isSuperAdmin()));
```

The page shows to the users who have something to see or do: a folder they can view, folders they can add at the root, or linked media they can view. `authorize()` adds an extra condition on top of that. Other options: `navigationIcon()`, `navigationLabel()`, `slug()` (default `media`).

The core works without Filament:

```php
use Bza\MediaManager\Ability;
use Bza\MediaManager\Grantee;
use Bza\MediaManager\Grants;
use Bza\MediaManager\MediaAccess;
use Bza\MediaManager\MediaLibrary;
use Bza\MediaManager\Target;

$library = app(MediaLibrary::class);
$folder = $library->createFolder(null, 'Brochures');
$media = $library->upload($folder, $path, 'Prices 2026.pdf');

app(Grants::class)->replace(Target::folder($folder), [
    ['grantee' => Grantee::of($editorRole), 'abilities' => ['view' => true, 'create' => true]],
]);

app(MediaAccess::class)->allowsFolder($user, Ability::Create, $folder);
```

`MediaLibrary` does not check rights: ask `MediaAccess` first. A refused operation throws a `MediaManagerException` with a translated message.

## Configuration

```bash
php artisan vendor:publish --tag=media-manager-config
php artisan vendor:publish --tag=media-manager-translations
```

| Key | Default | |
|-----|---------|-|
| `disk` | `null` | Disk of the uploaded files; null means `media-library.disk_name` |
| `collection` | `files` | The folders' media collection |
| `path_generator` | `FolderPathGenerator` | Null keeps the application's generator |
| `directory` | `media-manager` | Root directory of the uploaded files |
| `max_file_size` | `null` | In bytes; null means `media-library.max_file_size` |
| `accepted_file_types` | `[]` | MIME types; empty accepts all |
| `grantee_resolver` | `UserAndRolesGranteeResolver` | Lists a user's grantees |
| `grantees` | `null` | Who rights can be given to: model class => attribute shown; null means spatie roles and the default guard's users |
| `linked_media` | `true` | List the media of the other models |

Translations: French, English, German.

## Layout

```
src/
├── MediaLibrary.php            Folders and files: create, rename, move, delete, upload
├── MediaAccess.php             Who may do what: inheritance, overrides, super user
├── Grants.php                  Reads and replaces the rights of a target
├── LinkedMedia.php             The other models' media, read-only
├── Ability.php, Target.php, Grantee.php
├── Contracts/GranteeResolver.php
├── Models/                     MediaFolder, FolderGrant
├── Support/                    FolderPathGenerator, UserAndRolesGranteeResolver
└── Filament/                   MediaManagerPlugin, Pages/MediaManagerPage
```

## Tests

```bash
php artisan test packages/media-manager/tests
```
