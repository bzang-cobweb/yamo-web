<?php

namespace Bza\ConfigViewer\Sources;

use Bza\ConfigViewer\Contracts\ConfigSource;
use Bza\ConfigViewer\Data\ConfigFile;
use Bza\ConfigViewer\Origin;
use Bza\ConfigViewer\Support\PhpFileFinder;

/**
 * The files of the application's config directory, keyed the way Laravel loads
 * them: "config/services.php" under "services", "config/a/b.php" under "a.b".
 */
class ApplicationConfigSource implements ConfigSource
{
    private readonly string $directory;

    public function __construct(private readonly PhpFileFinder $finder, ?string $directory = null)
    {
        $this->directory = $directory ?? config_path();
    }

    public function files(): iterable
    {
        foreach ($this->finder->find($this->directory) as $key => $path) {
            yield new ConfigFile($key, $path, Origin::Application);
        }
    }
}
