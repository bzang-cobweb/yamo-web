<?php

namespace Tests\Unit;

use App\Support\Media\ModuleFieldPathGenerator;
use PHPUnit\Framework\TestCase;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class ModuleFieldPathGeneratorTest extends TestCase
{
    private function media(string $modelType, string $collection): Media
    {
        $media = new Media;
        $media->model_type = $modelType;
        $media->collection_name = $collection;
        $media->uuid = '0f6c1e2a-1111-4222-8333-444455556666';

        return $media;
    }

    public function test_a_module_media_is_stored_under_module_then_field_then_uuid(): void
    {
        $generator = new ModuleFieldPathGenerator;
        $media = $this->media('Modules\Auth\Models\User', 'avatar');

        $this->assertSame('auth/avatar/0f6c1e2a-1111-4222-8333-444455556666/', $generator->getPath($media));
        $this->assertSame('auth/avatar/0f6c1e2a-1111-4222-8333-444455556666/conversions/', $generator->getPathForConversions($media));
    }

    public function test_module_and_field_are_kebab_cased_and_a_model_outside_modules_goes_to_app(): void
    {
        $generator = new ModuleFieldPathGenerator;

        $this->assertStringStartsWith('event-booking/cover-image/', $generator->getPath($this->media('Modules\EventBooking\Models\Event', 'coverImage')));
        $this->assertStringStartsWith('app/document/', $generator->getPath($this->media('App\Models\Page', 'document')));
    }
}
