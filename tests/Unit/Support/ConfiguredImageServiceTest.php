<?php

namespace Tests\Unit\Support;

use App\Support\Media\ConfiguredImageService;
use Modules\Form\Rules\MinImageSide;
use Tests\TestCase;

/**
 * Les réglages d'une image, lus sous sa clé (décisions 2026-11-24-image-service-abstrait et
 * 2026-11-24-image-taille-minimale-enregistree).
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

    public function test_the_minimum_is_the_kept_original_when_there_is_one(): void
    {
        $service = $this->service();
        config(['testing.image' => ['conversions' => ['card' => 540], 'original_size' => 2048]]);

        // L'original gardé peut s'ouvrir en grand : il fixe le minimum (décision 2026-11-24-image-taille-minimale-enregistree).
        $this->assertSame(2048, $service::storedSize());
        $this->assertSame(2048, $service::minUploadSize());
        $this->assertSame(1, $service::maxFiles());
    }
}
