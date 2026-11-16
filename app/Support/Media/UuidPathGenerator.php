<?php

namespace App\Support\Media;

use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\MediaLibrary\Support\PathGenerator\PathGenerator;

/**
 * Range chaque média sous son UUID plutôt que sous son numéro de ligne : l'adresse
 * d'un fichier public ne permet pas d'en deviner une autre.
 */
class UuidPathGenerator implements PathGenerator
{
    public function getPath(Media $media): string
    {
        return $media->uuid.'/';
    }

    public function getPathForConversions(Media $media): string
    {
        return $media->uuid.'/conversions/';
    }

    public function getPathForResponsiveImages(Media $media): string
    {
        return $media->uuid.'/responsive-images/';
    }
}
