<?php

namespace Tests\Unit\Support;

use App\Support\Media\ConfiguredImageService;
use Modules\Form\Rules\MinImageSide;
use Spatie\Image\Enums\Fit;
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
        $this->assertInstanceOf(MinImageSide::class, $service::uploadRules()[1]);
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

    public function test_the_rule_follows_the_frame_and_the_fit(): void
    {
        config(['testing.image' => ['conversions' => ['card' => 540, 'page' => 1200]]]);

        // Entière dans un carré : le plus grand côté.
        $square = $this->service();
        $this->assertEquals(new MinImageSide(1200), $square::uploadRules()[1]);
        // Toujours le maximum, 10 000 sans réglage (décision 2026-11-24-image-dimension-maximale).
        $this->assertSame('dimensions:max_width=10000,max_height=10000', (string) $square::uploadRules()[0]);

        // Recadrée : les deux côtés (décision 2026-11-24-image-regle-minimum-par-cadre).
        $cropped = get_class(new class extends ConfiguredImageService
        {
            protected static function configKey(): string
            {
                return 'testing.image';
            }

            protected static function collection(): string
            {
                return 'images';
            }

            protected static function fit(): Fit
            {
                return Fit::Crop;
            }
        });
        $this->assertSame('dimensions:max_width=10000,max_height=10000,min_width=1200,min_height=1200', (string) $cropped::uploadRules()[0]);

        // Entière, bornée par la largeur : la largeur.
        $wide = get_class(new class extends ConfiguredImageService
        {
            protected static function configKey(): string
            {
                return 'testing.image';
            }

            protected static function collection(): string
            {
                return 'images';
            }

            public static function bounds(int $size): array
            {
                return [$size, 4 * $size];
            }
        });
        $this->assertSame('dimensions:max_width=10000,max_height=10000,min_width=1200', (string) $wide::uploadRules()[0]);

        // Le maximum se règle.
        config(['testing.image.max_dimension' => 6000]);
        $this->assertSame('dimensions:max_width=6000,max_height=6000,min_width=1200', (string) $wide::uploadRules()[0]);
    }
}
