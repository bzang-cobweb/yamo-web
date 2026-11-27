<?php

namespace Bza\MediaManager\Support;

use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\MediaLibrary\Support\PathGenerator\PathGenerator;

/**
 * Stores each uploaded file under "<directory>/<uuid>/". The path depends on
 * neither the folder nor the name, so moving a file to another folder only
 * changes a database row and keeps its address.
 */
class FolderPathGenerator implements PathGenerator
{
    public function getPath(Media $media): string
    {
        return trim((string) config('media-manager.directory', 'media-manager'), '/').'/'.($media->uuid ?: $media->getKey()).'/';
    }

    public function getPathForConversions(Media $media): string
    {
        return $this->getPath($media).'conversions/';
    }

    public function getPathForResponsiveImages(Media $media): string
    {
        return $this->getPath($media).'responsive-images/';
    }
}
