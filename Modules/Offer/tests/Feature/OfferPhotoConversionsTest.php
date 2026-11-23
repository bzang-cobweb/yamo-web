<?php

namespace Modules\Offer\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Modules\Offer\Models\Offer;
use Modules\Offer\Services\OfferPhotoService;
use Tests\TestCase;

/**
 * Une conversion des photos d'une offre par endroit où elles s'affichent, réglée dans la configuration (décision
 * 2026-11-24-avatar-photos-offre-conversions).
 */
class OfferPhotoConversionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_each_conversion_is_produced_at_the_width_of_the_configuration(): void
    {
        Storage::fake('public');
        config(['offer.photos.conversions.list' => 700]);
        $offer = Offer::factory()->create();

        // Une photo en hauteur : c'est sa largeur qui est bornée.
        $file = UploadedFile::fake()->image('portrait.jpg', 3000, 4000);
        $media = app(OfferPhotoService::class)->store($offer, $file->getRealPath());

        $this->assertSame([1920, 2560], array_slice(getimagesize($media->getPath()), 0, 2));
        $widths = [];
        foreach (['web', 'detail', 'list', 'home', 'form'] as $name) {
            $this->assertTrue($media->fresh()->hasGeneratedConversion($name), $name);
            $widths[$name] = getimagesize(Storage::disk('public')->path($media->getPathRelativeToRoot($name)))[0];
        }
        $this->assertSame(['web' => 1920, 'detail' => 1200, 'list' => 700, 'home' => 540, 'form' => 320], $widths);
        $this->assertStringEndsWith('-home.webp', (string) $offer->fresh()->photoUrl('home'));
    }

    public function test_without_its_conversion_the_photo_itself_is_served(): void
    {
        Storage::fake('public');
        $offer = Offer::factory()->create();
        $file = UploadedFile::fake()->image('salon.jpg', 800, 600);
        $media = app(OfferPhotoService::class)->store($offer, $file->getRealPath());
        $media->markAsConversionNotGenerated('list');
        $media->save();

        $this->assertSame($media->getUrl(), $offer->fresh()->photoUrl('list'));
        $this->assertSame([$media->getUrl()], $offer->fresh()->photoUrls('list'));
    }
}
