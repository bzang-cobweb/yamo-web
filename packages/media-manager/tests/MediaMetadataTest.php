<?php

namespace Bza\MediaManager\Tests;

use Bza\MediaManager\MediaLibrary;
use Bza\MediaManager\MediaMetadata;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase;
use Illuminate\Support\Facades\Storage;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * A file's metadata: texts per locale, credit, free pairs, kept in its custom
 * properties, for the manager's files and the other models' media alike.
 */
class MediaMetadataTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        config(['media-library.disk_name' => 'public', 'media-manager.locales' => ['fr', 'en'], 'app.fallback_locale' => 'en']);
    }

    private function metadata(): MediaMetadata
    {
        return $this->app->make(MediaMetadata::class);
    }

    private function file(): Media
    {
        $library = $this->app->make(MediaLibrary::class);
        $path = tempnam(sys_get_temp_dir(), 'media');
        file_put_contents($path, 'content');

        try {
            return $library->upload($library->createFolder(null, 'Docs'), $path, 'photo.txt');
        } finally {
            @unlink($path);
        }
    }

    public function test_the_metadata_are_kept_cleaned_in_the_custom_properties(): void
    {
        $media = $this->file();
        $media->setCustomProperty('other', 'kept')->save();

        $this->metadata()->update($media, [
            'alt' => ['fr' => ' Une plage ', 'en' => '', 'de' => 'ignored'],
            'title' => ['fr' => null],
            'credit' => '© Studio',
            'extra' => ['source' => 'Shooting 2026', ' ' => 'no key', 'empty' => ''],
        ]);

        $media = $media->fresh();
        $this->assertSame(['alt' => ['fr' => 'Une plage'], 'credit' => '© Studio', 'extra' => ['source' => 'Shooting 2026']], $media->getCustomProperty('metadata'));
        $this->assertSame('kept', $media->getCustomProperty('other'));
        $this->assertSame(['fr' => 'Une plage', 'en' => ''], $this->metadata()->get($media)['alt']);
        $this->assertSame(['fr' => '', 'en' => ''], $this->metadata()->get($media)['caption']);
    }

    public function test_a_text_falls_back_to_another_locale(): void
    {
        $media = $this->file();
        $this->metadata()->update($media, ['alt' => ['fr' => 'Une plage']]);

        $this->assertSame('Une plage', $this->metadata()->text($media, 'alt', 'en'));
        $this->assertNull($this->metadata()->text($media, 'caption', 'fr'));
    }

    public function test_emptying_every_field_forgets_the_metadata(): void
    {
        $media = $this->file();
        $this->metadata()->update($media, ['credit' => 'Someone']);
        $this->metadata()->update($media, ['credit' => '']);

        $this->assertFalse($media->fresh()->hasCustomProperty('metadata'));
    }

    public function test_the_media_of_other_models_get_metadata_too(): void
    {
        $media = $this->file();
        $media->forceFill(['model_type' => 'user', 'collection_name' => 'avatar'])->save();

        $this->metadata()->update($media, ['credit' => 'Someone']);

        $this->assertSame(['credit' => 'Someone'], $media->fresh()->getCustomProperty('metadata'));
    }
}
