<?php

namespace Modules\Offer\Services;

use App\Support\Media\ConfiguredImageService;
use App\Support\MediaUrl;
use Illuminate\Support\Facades\DB;
use Modules\Form\Models\FormUpload;
use Modules\Offer\Models\Offer;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * Les photos d'une offre saisies dans l'app (décision
 * 2026-11-19-contrat-champ-upload-photos) : la valeur du champ `photos` est la
 * liste ordonnée des photos voulues, celles déjà enregistrées (identifiant de
 * média) et celles envoyées entre-temps (fichiers en attente du compte). Les
 * nouvelles sont optimisées comme la photo de profil ; celles qui ne sont plus
 * citées sont retirées ; l'ordre reçu devient l'ordre de l'offre.
 */
class OfferPhotoService extends ConfiguredImageService
{
    /**
     * Ses réglages : `config('offer.photos')`, `OFFER_PHOTOS_*` (types, poids, nombre, qualité, conversions ; décision
     * 2026-11-24-avatar-photos-offre-conversions), lus par `ConfiguredImageService` (décision
     * 2026-11-24-image-service-abstrait). La photo enregistrée a la largeur de la plus grande conversion.
     */
    protected static function configKey(): string
    {
        return 'offer.photos';
    }

    protected static function collection(): string
    {
        return Offer::PHOTOS;
    }

    protected static function mediaName(): string
    {
        return 'photo';
    }

    /**
     * Le cadre d'une largeur `$size` : les photos remplissent un cadre 16:9 dans l'app, c'est leur largeur qui
     * compte ; une photo en hauteur garde ainsi toute sa largeur. La hauteur est bornée à quatre fois la largeur,
     * contre les images démesurées.
     *
     * @return array{0: int, 1: int}
     */
    public static function bounds(int $size): array
    {
        return [$size, 4 * $size];
    }

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
            // Les vignettes du champ (décision 2026-11-24-avatar-photos-offre-conversions).
            ->mapWithKeys(fn (Media $media): array => [(string) $media->uuid => (string) MediaUrl::onRequestHost(Offer::mediaUrl($media, 'form'))])
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
            $media = $existing->get($id) ?? ($uploads->has($id) ? $this->storeUpload($offer, $uploads->get($id)) : null);
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
     * Un fichier en attente du formulaire de l'app devient une photo de l'offre ; il est retiré.
     */
    private function storeUpload(Offer $offer, FormUpload $upload): Media
    {
        $media = $this->store($offer, $upload->absolutePath(), $upload->uuid);
        $upload->discard();

        return $media;
    }
}
