<?php

namespace Bza\MediaManager\Tests;

use PHPUnit\Framework\TestCase;

/**
 * French, English and German carry the same texts.
 */
class TranslationsTest extends TestCase
{
    /**
     * @param  array<string, mixed>  $texts
     * @return list<string>
     */
    private function keys(array $texts, string $prefix = ''): array
    {
        $keys = [];

        foreach ($texts as $key => $value) {
            $path = $prefix === '' ? (string) $key : $prefix.'.'.$key;
            $keys = [...$keys, ...(is_array($value) ? $this->keys($value, $path) : [$path])];
        }

        sort($keys);

        return $keys;
    }

    public function test_every_language_has_every_text(): void
    {
        $french = $this->keys(require __DIR__.'/../resources/lang/fr/media-manager.php');

        foreach (['en', 'de'] as $locale) {
            $this->assertSame($french, $this->keys(require __DIR__.'/../resources/lang/'.$locale.'/media-manager.php'), "Texts differ in {$locale}.");
        }
    }
}
