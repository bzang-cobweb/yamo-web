<?php

namespace Tests\Unit\Support;

use App\Support\Media\ConfiguredImageService;
use Modules\Form\Rules\MinImageSide;
use Tests\TestCase;

/**
 * Les réglages d'une image, lus sous sa clé (décisions 2026-11-24-image-service-abstrait et
 * 2026-11-24-image-taille-minimale-max).
 */
class ConfiguredImageServiceTest extends TestCase
{
    private function service(): string
    {
        return get_class(new class extends ConfiguredImageService
        {
            protected static function configKey(): string
            {
                return 'testing.image';
            }

            protected static function collection(): string
            {
                return 'images';
            }
        });
    }

    public function test_the_minimum_is_the_largest_conversion(): void
    {
        $service = $this->service();
        config(['testing.image' => ['conversions' => ['card' => 540, 'page' => 1200]]]);

        $this->assertSame(1200, $service::minUploadSize());
        $this->assertInstanceOf(MinImageSide::class, $service::uploadRules()[0]);
        // Sans original gardé : la plus grande conversion.
        $this->assertSame(1200, $service::storedSize());
    }

    public function test_the_kept_original_does_not_change_the_minimum_and_without_conversion_there_is_none(): void
    {
        $service = $this->service();
        config(['testing.image' => ['conversions' => ['card' => 540], 'original_size' => 2048]]);

        $this->assertSame(540, $service::minUploadSize());
        $this->assertSame(1, $service::maxFiles());
        $this->assertSame(2048, $service::storedSize());

        config(['testing.image' => ['conversions' => []]]);
        $this->assertSame(0, $service::minUploadSize());
        $this->assertSame([], $service::uploadRules());
    }
}
