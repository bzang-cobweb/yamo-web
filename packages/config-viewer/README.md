# bza/laravel-config-viewer

Read-only viewer for the **effective** Laravel configuration — application files,
`nwidart/laravel-modules` module files and package defaults — with sensitive values
masked. Ships with a Filament 5 adapter; the core has no Filament dependency.

## Architecture

```
src/
├── ConfigInspector.php            Core entry point: groups, entries, search, masking
├── ConfigViewerServiceProvider.php Registers the core (no Filament)
├── Origin.php                     application | module | package
├── Contracts/
│   ├── ConfigSource.php           Attributes files to config keys
│   └── ValueMasker.php            Decides which values are sensitive
├── Data/                          Immutable DTOs (toArray() for APIs)
│   ├── ConfigFile.php, ConfigGroup.php, ConfigEntry.php, ConfigQuery.php
├── Sources/
│   ├── ApplicationConfigSource.php config/ directory, nested files included
│   └── ModuleConfigSource.php     Enabled modules (no-op without nwidart)
├── Support/                       Flattener, formatter, masker, file finder, value type
└── Filament/                      Adapter only: plugin + page, no logic
```

Values are always read from the config repository, so the viewer shows what the
application really uses (cached config, runtime overrides). Sources only say which
files feed which key. Masking happens in the core: a masked value never reaches an
adapter.

## Filament

```php
use Bza\ConfigViewer\Filament\ConfigViewerPlugin;

$panel->plugin(ConfigViewerPlugin::make()
    ->navigationGroup('Settings')         // string, enum or closure
    ->navigationSort(110)
    ->slug('configuration')
    ->authorize(fn (): bool => auth()->user()->isSuperAdmin()));
```

Without `authorize()`, every user of the panel can open the page.

## Another adapter

Resolve the inspector and map its DTOs:

```php
use Bza\ConfigViewer\ConfigInspector;
use Bza\ConfigViewer\Data\ConfigQuery;

$inspector = app(ConfigInspector::class);
$entries = $inspector->entries(new ConfigQuery(group: 'mail', search: 'smtp'));
$partner = $inspector->entries(new ConfigQuery(module: 'Partner')); // $inspector->modules() lists the names

return array_map(fn ($entry) => $entry->toArray(), $entries); // JSON API, Inertia, CLI…
```

## Configuration

`php artisan vendor:publish --tag=config-viewer-config`

- `sources`: `ConfigSource` classes (add one for another modules package).
- `mask.segments` / `mask.keys`: patterns of sensitive keys; `mask.placeholder`.
- `hidden`: keys never listed (`services.stripe`, or a whole group: `services`).

## Tests

Core tests run on a bare `Illuminate\Config\Repository`, without an application:
`php artisan test --testsuite=Packages`.
