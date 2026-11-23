<?php

namespace Modules\Partner\Services;

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
class PartnerLogoService
{
    /** Le nom du champ du formulaire. */
    public const FIELD = 'logo';

    /**
     * Les types de fichier acceptés (`config('partner.logo.mime_types')`, `PARTNER_LOGO_MIME_TYPES`).
     *
     * @return list<string>
     */
    public static function acceptedMimeTypes(): array
    {
        return config('partner.logo.mime_types');
    }

    /** La taille maximale d'un envoi, en kilo-octets (`PARTNER_LOGO_MAX_UPLOAD_KB`). */
    public static function maxUploadKb(): int
    {
        return (int) config('partner.logo.max_upload_kb');
    }

    /**
     * Le plus grand côté du logo enregistré, en pixels : l'original envoyé, plafonné (`PARTNER_LOGO_ORIGINAL_SIZE`) ;
     * les conversions en sont tirées (décision 2026-11-24-partenaire-logo-original).
     */
    public static function storedSize(): int
    {
        return (int) config('partner.logo.original_size');
    }

    /**
     * Le plus grand côté minimal d'un logo envoyé : la conversion `list`, la plus grande place où il s'affiche (cartes
     * de « Mes partenaires »), pour qu'elle ne soit jamais un agrandissement (décision 2026-11-24-partenaire-logo-original).
     */
    public static function minUploadSize(): int
    {
        return (int) config('partner.logo.conversions.list');
    }

    /** La qualité du WebP enregistré (`PARTNER_LOGO_QUALITY`). */
    private function quality(): int
    {
        return (int) config('partner.logo.quality');
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
                ->quality($this->quality())
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
