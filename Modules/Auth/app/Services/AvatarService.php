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

    public const THUMB = 'thumb';

    /** Types acceptés à l'envoi (avant optimisation). */
    public const ACCEPTED_MIME_TYPES = ['image/jpeg', 'image/png', 'image/webp'];

    /** Extensions correspondantes, pour la règle de validation `mimes`. */
    public const ACCEPTED_EXTENSIONS = ['jpg', 'jpeg', 'png', 'webp'];

    /** Taille maximale à l'envoi, en kilo-octets (5 Mo). */
    public const MAX_UPLOAD_KB = 5120;

    /** Côté maximal de l'image enregistrée, en pixels. */
    public const STORED_SIZE = 512;

    /** Côté de la vignette affichée, en pixels. */
    public const THUMB_SIZE = 256;

    public const QUALITY = 80;

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
            'mimes:'.implode(',', self::ACCEPTED_EXTENSIONS),
            'max:'.self::MAX_UPLOAD_KB,
            // Garde-fou contre les images démesurées à décoder.
            'dimensions:max_width=10000,max_height=10000',
        ];
    }

    /**
     * Optimise l'image de `$sourcePath` et l'enregistre comme photo du compte, à la
     * place de la précédente. Le fichier source n'est pas modifié.
     */
    public function store(User $user, string $sourcePath): Media
    {
        $base = (string) tempnam(sys_get_temp_dir(), 'avatar');
        $optimised = $base.'.webp';

        try {
            Image::load($sourcePath)
                ->orientation()
                ->fit(Fit::Max, self::STORED_SIZE, self::STORED_SIZE)
                ->quality(self::QUALITY)
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
