<?php

namespace Bza\ConfigViewer\Tests;

use Bza\ConfigViewer\ConfigInspector;
use Bza\ConfigViewer\Data\ConfigEntry;
use Bza\ConfigViewer\Data\ConfigQuery;
use Bza\ConfigViewer\Origin;
use Bza\ConfigViewer\Sources\ApplicationConfigSource;
use Bza\ConfigViewer\Support\KeyPatternMasker;
use Bza\ConfigViewer\Support\PhpFileFinder;
use Bza\ConfigViewer\Support\ValueType;
use Illuminate\Config\Repository;
use PHPUnit\Framework\TestCase;

/**
 * The core runs on a bare config repository: no application, no Filament.
 */
class ConfigInspectorTest extends TestCase
{
    /**
     * @param  list<string>  $hidden
     */
    private function inspector(array $hidden = []): ConfigInspector
    {
        $config = new Repository([
            'app' => ['name' => 'Demo', 'key' => 'base64:secret', 'debug' => false],
            'shop' => ['currency' => 'CHF', 'name' => 'Shop', 'payment' => ['provider' => 'stripe', 'api_key' => 'sk_live']],
            'nested' => ['deep' => ['enabled' => true]],
            'cors' => ['paths' => ['api/*']],
        ]);

        return new ConfigInspector(
            config: $config,
            sources: [new ApplicationConfigSource(new PhpFileFinder, __DIR__.'/fixtures/config'), new FakeModuleConfigSource],
            masker: new KeyPatternMasker(['key', '*_key'], [], '***'),
            hidden: $hidden,
        );
    }

    /**
     * @param  list<ConfigEntry>  $entries
     * @return list<string>
     */
    private function keys(array $entries): array
    {
        return array_map(fn (ConfigEntry $entry): string => $entry->key, $entries);
    }

    public function test_groups_are_the_repository_keys_sorted(): void
    {
        $this->assertSame(['app', 'cors', 'nested', 'shop'], array_keys($this->inspector()->groups()));
    }

    public function test_a_group_mixes_application_and_module_files(): void
    {
        $shop = $this->inspector()->group('shop');

        $this->assertSame([Origin::Application, Origin::Module], $shop->origins());
        $this->assertSame(['Shop'], $shop->modules());
        $this->assertCount(4, $shop->files);
    }

    public function test_a_key_without_file_is_a_package_group(): void
    {
        $this->assertSame([Origin::Package], $this->inspector()->group('cors')->origins());
        $this->assertSame([Origin::Application], $this->inspector()->group('nested')->origins());
    }

    public function test_sensitive_values_never_leave_the_core(): void
    {
        $entry = $this->inspector()->entry('app.key');

        $this->assertTrue($entry->masked);
        $this->assertNull($entry->value);
        $this->assertSame('***', $entry->display);
        $this->assertStringNotContainsString('base64:secret', json_encode($this->inspector()->entries()));
    }

    public function test_search_matches_keys_and_visible_values_only(): void
    {
        $inspector = $this->inspector();

        $this->assertSame(['shop.currency'], $this->keys($inspector->entries(new ConfigQuery(search: 'chf'))));
        $this->assertSame([], $this->keys($inspector->entries(new ConfigQuery(search: 'sk_live'))));
        $this->assertSame(['shop.payment.api_key'], $this->keys($inspector->entries(new ConfigQuery(search: 'api_key'))));
    }

    public function test_entries_can_be_narrowed_to_a_group_or_an_origin(): void
    {
        $inspector = $this->inspector();

        $this->assertSame(['nested.deep.enabled'], $this->keys($inspector->entries(new ConfigQuery(group: 'nested'))));
        $this->assertSame(['cors.paths'], $this->keys($inspector->entries(new ConfigQuery(origin: Origin::Package))));
        $this->assertSame(['shop.currency', 'shop.name', 'shop.payment.provider', 'shop.payment.api_key'], $this->keys($inspector->entries(new ConfigQuery(origin: Origin::Module))));
    }

    public function test_entries_can_be_narrowed_to_a_module(): void
    {
        $inspector = $this->inspector();

        $this->assertSame(['Shop'], $inspector->modules());
        $this->assertSame(['shop.currency', 'shop.name', 'shop.payment.provider', 'shop.payment.api_key'], $this->keys($inspector->entries(new ConfigQuery(module: 'Shop'))));
        $this->assertSame([], $inspector->entries(new ConfigQuery(module: 'Unknown')));
    }

    public function test_entries_can_be_narrowed_to_a_value_type(): void
    {
        $inspector = $this->inspector();

        $this->assertSame(['app.debug', 'nested.deep.enabled'], $this->keys($inspector->entries(new ConfigQuery(types: [ValueType::Boolean]))));
        $this->assertSame(['cors.paths'], $this->keys($inspector->entries(new ConfigQuery(types: [ValueType::Array, ValueType::Null]))));
        // A masked value keeps its type, not its content.
        $this->assertContains('app.key', $this->keys($inspector->entries(new ConfigQuery(types: [ValueType::String]))));
    }

    public function test_hidden_patterns_remove_keys_or_whole_groups(): void
    {
        $inspector = $this->inspector(['cors', 'shop.payment']);

        $this->assertArrayNotHasKey('cors', $inspector->groups());
        $this->assertSame(['shop.currency', 'shop.name'], $this->keys($inspector->entries(new ConfigQuery(group: 'shop'))));
        $this->assertNull($inspector->entry('shop.payment.provider'));
    }

    public function test_values_are_displayed_as_text(): void
    {
        $inspector = $this->inspector();

        $this->assertSame('false', $inspector->entry('app.debug')->display);
        $this->assertSame('["api/*"]', $inspector->entry('cors.paths')->display);
    }
}
