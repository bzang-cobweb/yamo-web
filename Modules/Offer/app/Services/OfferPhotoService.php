<?php

namespace Modules\Offer\Services;

use App\Support\MediaUrl;
use Illuminate\Support\Facades\DB;
use Modules\Form\Models\FormUpload;
use Modules\Offer\Models\Offer;
use Spatie\Image\Enums\Fit;
use Spatie\Image\Image;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * Les photos d'une offre saisies dans l'app (décision
 * 2026-11-19-contrat-champ-upload-photos) : la valeur du champ `photos` est la
 * liste ordonnée des photos voulues, celles déjà enregistrées (identifiant de
 * média) et celles envoyées entre-temps (fichiers en attente du compte). Les
 * nouvelles sont optimisées comme la photo de profil ; celles qui ne sont plus
 * citées sont retirées ; l'ordre reçu devient l'ordre de l'offre.
 */
class OfferPhotoService
{
    /** Plus grand côté d'une photo enregistrée, en pixels. */
    public const STORED_SIZE = 1600;

    public const QUALITY = 80;

    /** Types acceptés (ceux de la collection `photos`). */
    public const ACCEPTED_MIME_TYPES = ['image/jpeg', 'image/png', 'image/webp'];

    /** Taille maximale d'un fichier envoyé, en Ko. */
    public const MAX_UPLOAD_KB = 10240;

    public const MAX_FILES = 20;

    /**
     * Les photos enregistrées de l'offre, dans l'ordre : identifiant => adresse (sur
     * l'hôte de la requête, comme la fiche).
     *
     * @return array<string, string>
     */
    public function current(?Offer $offer): array
    {
        if ($offer === null) {
            return [];
        }

        return $offer->getMedia(Offer::PHOTOS)
            ->mapWithKeys(fn (Media $media): array => [(string) $media->uuid => (string) MediaUrl::onRequestHost($media->getUrl())])
            ->all();
    }

    /**
     * Remplace les photos de l'offre par `$ids`, dans cet ordre.
     *
     * @param  list<string>  $ids  identifiants validés par le champ `photos`
     */
    public function sync(Offer $offer, int|string $userId, array $ids): void
    {
        $existing = $offer->getMedia(Offer::PHOTOS)->keyBy(fn (Media $media): string => (string) $media->uuid);
        $uploads = FormUpload::pending($userId, 'photos')->whereIn('uuid', $ids)->get()->keyBy('uuid');

        $ordered = [];
        foreach ($ids as $id) {
            $media = $existing->get($id) ?? ($uploads->has($id) ? $this->store($offer, $uploads->get($id)) : null);
            if ($media !== null) {
                $ordered[] = $media->getKey();
            }
        }

        // Retirées : celles qui ne sont plus citées.
        $existing->reject(fn (Media $media): bool => in_array($media->getKey(), $ordered, true))->each(fn (Media $media) => $media->delete());

        DB::transaction(fn () => Media::setNewOrder($ordered));
        $offer->unsetRelation('media');
    }

    /**
     * Optimise un fichier en attente (WebP, 1600 px au plus, original non gardé) et
     * l'ajoute aux photos de l'offre ; le fichier en attente est retiré.
     */
    private function store(Offer $offer, FormUpload $upload): Media
    {
        $base = (string) tempnam(sys_get_temp_dir(), 'offer');
        $optimised = $base.'.webp';

        try {
            Image::load($upload->absolutePath())
                ->orientation()
                ->fit(Fit::Max, self::STORED_SIZE, self::STORED_SIZE)
                ->quality(self::QUALITY)
                ->format('webp')
                ->save($optimised);

            $media = $offer->addMedia($optimised)
                ->usingName('photo')
                ->usingFileName($upload->uuid.'.webp')
                ->toMediaCollection(Offer::PHOTOS);
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
