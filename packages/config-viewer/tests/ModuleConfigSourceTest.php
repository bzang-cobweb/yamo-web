<?php

namespace Bza\ConfigViewer\Tests;

use Bza\ConfigViewer\Data\ConfigFile;
use Bza\ConfigViewer\Origin;
use PHPUnit\Framework\TestCase;

class ModuleConfigSourceTest extends TestCase
{
    public function test_it_keys_module_files_like_the_module_service_provider(): void
    {
        $files = iterator_to_array((new FakeModuleConfigSource)->files(), false);

        $keys = array_map(fn (ConfigFile $file): string => $file->key, $files);
        sort($keys);

        // config.php under the module key, shop.php deduplicated, others nested.
        $this->assertSame(['shop', 'shop', 'shop.payment'], $keys);
        $this->assertSame(['shop'], array_values(array_unique(array_map(fn (ConfigFile $file): string => $file->group(), $files))));
        $this->assertSame(['Shop'], array_values(array_unique(array_map(fn (ConfigFile $file): ?string => $file->module, $files))));
        $this->assertSame([Origin::Module], array_values(array_unique(array_map(fn (ConfigFile $file): Origin => $file->origin, $files), SORT_REGULAR)));
    }
}
