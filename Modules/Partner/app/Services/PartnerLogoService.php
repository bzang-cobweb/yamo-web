<?php

namespace Modules\Partner\Services;

use App\Support\Media\ConfiguredImageService;
use App\Support\MediaUrl;
use Illuminate\Support\Str;
use Modules\Form\Models\FormUpload;
use Modules\Partner\Models\Partner;
use Spatie\Image\Enums\Fit;
use Spatie\Image\Image;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * Le logo d'un partenaire, optimisé en WebP, un seul à la fois : envoyé depuis l'app (décision
 * 2026-11-24-partenaires-par-les-membres), comme les photos d'une offre, ou depuis le back-office (décision
 * 2026-11-24-partenaire-logo-optimise), comme la photo de profil.
 */
class PartnerLogoService extends ConfiguredImageService
{
    /** Le nom du champ du formulaire. */
    public const FIELD = 'logo';

    /**
     * Ses réglages : `config('partner.logo')`, `PARTNER_LOGO_*` (types, poids, qualité, plafond de l'original,
     * conversions ; décisions 2026-11-24-partenaire-logo-config et 2026-11-24-partenaire-logo-original), lus par
     * `ConfiguredImageService` (décision 2026-11-24-image-service-abstrait). L'original est gardé, plafonné.
     */
    protected static function configKey(): string
    {
        return 'partner.logo';
    }

    /**
     * Le plus grand côté minimal d'un logo envoyé : la conversion `list`, la plus grande place où il s'affiche (cartes
     * de « Mes partenaires »), pour qu'elle ne soit jamais un agrandissement (décision 2026-11-24-partenaire-logo-original).
     */
    public static function minUploadSize(): int
    {
        return static::conversionSize('list');
    }

    /**
     * Le logo actuel, pour le champ du formulaire : son identifiant (uuid du média) et son adresse.
     *
     * @return array<string, string>
     */
    public function current(?Partner $partner): array
    {
        $media = $partner?->getFirstMedia(Partner::LOGO);

        // L'aperçu du champ : la conversion `list`, pas l'original (décision 2026-11-24-partenaire-logo-original).
        return $media === null ? [] : [(string) $media->uuid => (string) MediaUrl::onRequestHost($partner->logoUrl('list'))];
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

        $this->storeUpload($partner, $upload);
    }

    /**
     * Un fichier en attente du formulaire de l'app devient le logo ; il est retiré.
     */
    private function storeUpload(Partner $partner, FormUpload $upload): Media
    {
        $media = $this->store($partner, $upload->absolutePath(), $upload->uuid);
        $upload->discard();

        return $media;
    }

    /**
     * Le fichier `$sourcePath` devient le logo : l'original gardé en WebP, plafonné sur son grand côté (`storedSize()`,
     * décision 2026-11-24-partenaire-logo-original), proportions gardées ; les conversions en sont tirées ; tailles et
     * qualité réglées dans `config('partner.logo')`. Le même pour l'app et le back-office (décision
     * 2026-11-24-partenaire-logo-optimise) ; `singleFile()` : il remplace l'ancien.
     */
    public function store(Partner $partner, string $sourcePath, ?string $name = null): Media
    {
        $base = (string) tempnam(sys_get_temp_dir(), 'partner');
        $optimised = $base.'.webp';

        try {
            Image::load($sourcePath)
                ->orientation()
                ->fit(Fit::Max, self::storedSize(), self::storedSize())
                ->quality(self::quality())
                ->format('webp')
                ->save($optimised);

            $media = $partner->addMedia($optimised)
                ->usingName('logo')
                ->usingFileName(($name ?? (string) Str::uuid()).'.webp')
                ->toMediaCollection(Partner::LOGO);
        } finally {
            foreach ([$base, $optimised] as $path) {
                if (is_file($path)) {
                    unlink($path);
                }
            }
        }

        $partner->unsetRelation('media');

        return $media;
    }
}
