<?php

namespace Modules\Auth\Services;

use Bza\Form\Models\FormUpload;
use Illuminate\Support\Facades\URL;
use Modules\Auth\Models\User;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * Les documents d'identité d'un compte (décision 2026-11-25-identification) : des photos de la pièce d'identité,
 * envoyées depuis l'app, que l'équipe regarde au back-office pour remplir la date d'identification. Gardés sur le
 * disque privé ; l'app ne les voit que par un lien signé de quelques minutes.
 */
class IdentityDocumentService
{
    /** Le champ du formulaire et la collection des médias. */
    public const FIELD = 'documents';

    public const COLLECTION = 'identity';

    /** Le pays qui a délivré la pièce et son type (décision 2026-11-27-identite-pays-et-type-de-piece). */
    public const COUNTRY_FIELD = 'identity_country_id';

    public const TYPE_FIELD = 'identity_document_type';

    /** Le disque privé (`storage/app/private`). */
    public const DISK = 'local';

    /** Au plus ce nombre de photos (recto, verso, d'autres pièces) (décision 2026-11-25-identite-plusieurs-photos). */
    public const MAX_FILES = 5;

    /** Le poids d'une photo, en Ko. */
    public const MAX_KB = 10240;

    /** La durée d'un lien d'aperçu, en minutes. */
    public const LINK_MINUTES = 10;

    /** @return list<string> */
    public static function acceptedMimeTypes(): array
    {
        // Des photos du document : l'app choisit dans la photothèque (un PDF demanderait un sélecteur de fichiers).
        return ['image/jpeg', 'image/png', 'image/webp', 'image/heic'];
    }

    /**
     * Les photos déjà envoyées, pour le champ du formulaire : uuid => lien signé d'aperçu, sur l'hôte de la requête
     * (l'app joint l'API par une autre adresse que `APP_URL`).
     *
     * @return array<string, string>
     */
    public function current(User $user): array
    {
        return $user->getMedia(self::COLLECTION)
            ->mapWithKeys(fn (Media $media): array => [(string) $media->uuid => $this->previewUrl($media)])
            ->all();
    }

    public function previewUrl(Media $media): string
    {
        $path = URL::temporarySignedRoute('api.v1.auth.identity-documents.show', now()->addMinutes(self::LINK_MINUTES), ['uuid' => $media->uuid], absolute: false);

        return request()->getSchemeAndHttpHost().$path;
    }

    /**
     * L'envoi du formulaire : le pays et le type de la pièce, puis ses photos.
     *
     * @param  array<string, mixed>  $data  données validées
     */
    public function submit(User $user, array $data): void
    {
        $user->forceFill([
            self::COUNTRY_FIELD => (int) $data[self::COUNTRY_FIELD],
            self::TYPE_FIELD => $data[self::TYPE_FIELD],
        ])->save();

        $this->sync($user, $data[self::FIELD] ?? []);
    }

    /**
     * Les photos envoyées : les nouvelles (en attente du compte) sont ajoutées ; celles retirées du champ sont
     * supprimées, sauf une fois le compte identifié : l'équipe a validé sur ces photos, elles restent
     * (décision 2026-11-25-identite-plusieurs-photos). Ajouter des photos ne retire pas l'identification.
     *
     * @param  list<string>  $ids
     */
    public function sync(User $user, array $ids): void
    {
        if (! $user->isIdentified()) {
            foreach ($user->getMedia(self::COLLECTION) as $media) {
                if (! in_array((string) $media->uuid, $ids, true)) {
                    $media->delete();
                }
            }
        }

        foreach (FormUpload::pending($user->getKey(), self::FIELD)->whereIn('uuid', $ids)->get() as $upload) {
            $user->addMedia($upload->absolutePath())
                ->preservingOriginal()
                ->usingFileName((string) basename($upload->path))
                ->toMediaCollection(self::COLLECTION, self::DISK);
            $upload->discard();
        }
    }
}
