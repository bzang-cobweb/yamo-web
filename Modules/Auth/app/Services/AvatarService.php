<?php

namespace Modules\Auth\Services;

use Modules\Auth\Models\User;
use Spatie\Image\Enums\Fit;
use Spatie\Image\Image;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * Photo de profil : seul domicile des règles d'envoi et de l'optimisation, pour
 * l'API comme pour le back-office. L'image reçue n'est jamais conservée telle
 * quelle : elle est redressée, réduite et réencodée en WebP avant d'être
 * enregistrée.
 */
final class AvatarService
{
    public const COLLECTION = 'avatar';

    /**
     * Les types acceptés à l'envoi, avant optimisation (`config('auth.avatar.mime_types')`, `AUTH_AVATAR_MIME_TYPES`).
     *
     * @return list<string>
     */
    public static function acceptedMimeTypes(): array
    {
        return config('auth.avatar.mime_types');
    }

    /** La taille maximale d'un envoi, en kilo-octets (`AUTH_AVATAR_MAX_UPLOAD_KB`). */
    public static function maxUploadKb(): int
    {
        return (int) config('auth.avatar.max_upload_kb');
    }

    /** La qualité du WebP enregistré et de ses conversions (`AUTH_AVATAR_QUALITY`). */
    public static function quality(): int
    {
        return (int) config('auth.avatar.quality');
    }

    /**
     * Le côté du carré enregistré, en pixels : la plus grande des conversions, dont il est la source (décision
     * 2026-11-24-avatar-photos-offre-conversions).
     */
    public static function storedSize(): int
    {
        $sizes = array_map('intval', (array) config('auth.avatar.conversions'));
        if ($sizes === []) {
            return 512;
        }

        return max($sizes);
    }

    /**
     * Règles de validation d'un fichier envoyé.
     *
     * @return list<string>
     */
    public static function rules(): array
    {
        return [
            // Un seul message par refus : le premier qui s'applique.
            'bail',
            'required',
            'file',
            'image',
            'mimetypes:'.implode(',', self::acceptedMimeTypes()),
            'max:'.self::maxUploadKb(),
            // Garde-fou contre les images démesurées à décoder.
            'dimensions:max_width=10000,max_height=10000',
        ];
    }

    /**
     * Optimise l'image de `$sourcePath` et l'enregistre comme photo du compte, à la
     * place de la précédente : un carré recadré au centre, de la plus grande des
     * conversions (`storedSize()`), en WebP. Le fichier source n'est pas modifié.
     */
    public function store(User $user, string $sourcePath): Media
    {
        $base = (string) tempnam(sys_get_temp_dir(), 'avatar');
        $optimised = $base.'.webp';

        try {
            Image::load($sourcePath)
                ->orientation()
                ->fit(Fit::Crop, self::storedSize(), self::storedSize())
                ->quality(self::quality())
                ->format('webp')
                ->save($optimised);

            $media = $user->addMedia($optimised)
                ->usingName('avatar')
                ->usingFileName('avatar.webp')
                ->toMediaCollection(self::COLLECTION);
        } finally {
            foreach ([$base, $optimised] as $path) {
                if (is_file($path)) {
                    unlink($path);
                }
            }
        }

        $user->unsetRelation('media');

        return $media;
    }

    /**
     * Retire la photo du compte (fichier et vignette compris).
     */
    public function remove(User $user): void
    {
        $user->clearMediaCollection(self::COLLECTION);
        $user->unsetRelation('media');
    }
}
