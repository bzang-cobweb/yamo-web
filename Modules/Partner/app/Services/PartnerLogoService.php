<?php

namespace Modules\Partner\Services;

use App\Support\MediaUrl;
use Modules\Form\Models\FormUpload;
use Modules\Partner\Models\Partner;
use Spatie\Image\Enums\Fit;
use Spatie\Image\Image;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * Le logo d'un partenaire envoyé depuis l'app (décision 2026-11-24-partenaires-par-les-membres), comme les photos
 * d'une offre : le fichier en attente du formulaire devient le logo, optimisé en WebP ; un seul à la fois.
 */
class PartnerLogoService
{
    /** Le nom du champ du formulaire. */
    public const FIELD = 'logo';

    public const ACCEPTED_MIME_TYPES = ['image/jpeg', 'image/png', 'image/webp'];

    /** Taille maximale d'un envoi, en kilo-octets. */
    public const MAX_UPLOAD_KB = 5120;

    /** Le plus grand côté du logo enregistré, en pixels. */
    private const STORED_SIZE = 512;

    private const QUALITY = 85;

    /**
     * Le logo actuel, pour le champ du formulaire : son identifiant (uuid du média) et son adresse.
     *
     * @return array<string, string>
     */
    public function current(?Partner $partner): array
    {
        $media = $partner?->getFirstMedia(Partner::LOGO);

        return $media === null ? [] : [(string) $media->uuid => (string) MediaUrl::onRequestHost($media->getUrl())];
    }

    /**
     * Le logo envoyé : l'actuel gardé s'il est encore là, un fichier en attente du compte `$userId` à sa place,
     * aucun : le logo est retiré.
     *
     * @param  list<string>  $ids
     */
    public function sync(Partner $partner, int|string $userId, array $ids): void
    {
        $current = $partner->getFirstMedia(Partner::LOGO);
        // L'actuel, encore là : rien à faire.
        if ($current !== null && in_array((string) $current->uuid, $ids, true)) {
            return;
        }

        $upload = $ids === [] ? null : FormUpload::pending($userId, self::FIELD)->whereIn('uuid', $ids)->first();
        if ($upload === null) {
            $partner->clearMediaCollection(Partner::LOGO);

            return;
        }

        $this->store($partner, $upload);
    }

    private function store(Partner $partner, FormUpload $upload): Media
    {
        $base = (string) tempnam(sys_get_temp_dir(), 'partner');
        $optimised = $base.'.webp';

        try {
            Image::load($upload->absolutePath())
                ->orientation()
                ->fit(Fit::Max, self::STORED_SIZE, self::STORED_SIZE)
                ->quality(self::QUALITY)
                ->format('webp')
                ->save($optimised);

            // `singleFile()` : le nouveau remplace l'ancien.
            $media = $partner->addMedia($optimised)
                ->usingName('logo')
                ->usingFileName($upload->uuid.'.webp')
                ->toMediaCollection(Partner::LOGO);
        } finally {
            foreach ([$base, $optimised] as $path) {
                if (is_file($path)) {
                    unlink($path);
                }
            }
        }

        $upload->discard();

        return $media;
    }
}
