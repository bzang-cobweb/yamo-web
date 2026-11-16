<?php

namespace App\Support\Media;

use Illuminate\Support\Str;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\MediaLibrary\Support\PathGenerator\PathGenerator;

/**
 * Range chaque média sous `<module>/<champ>/<uuid>/` (décision
 * 2026-11-16-contrat-textes-servis-par-api) :
 *
 * - le module, en minuscules avec tirets, déduit de la classe du modèle
 *   (`Modules\Auth\Models\User` → `auth`) ; `app` pour un modèle hors module ;
 * - le champ, nom de la collection medialibrary (`avatar`) ;
 * - l'UUID du média, pour qu'une adresse publique ne permette pas d'en deviner une autre.
 */
class ModuleFieldPathGenerator implements PathGenerator
{
    public function getPath(Media $media): string
    {
        return self::module($media->model_type).'/'.Str::kebab($media->collection_name).'/'.$media->uuid.'/';
    }

    public function getPathForConversions(Media $media): string
    {
        return $this->getPath($media).'conversions/';
    }

    public function getPathForResponsiveImages(Media $media): string
    {
        return $this->getPath($media).'responsive-images/';
    }

    /**
     * Répertoire du module d'une classe de modèle.
     */
    public static function module(string $modelClass): string
    {
        return preg_match('/^Modules\\\\([^\\\\]+)\\\\/', $modelClass, $matches) === 1
            ? Str::kebab($matches[1])
            : 'app';
    }
}
