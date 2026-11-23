<?php

namespace Modules\Auth\Services;

use App\Support\Media\ConfiguredImageService;
use Modules\Auth\Models\User;
use Spatie\Image\Enums\Fit;

/**
 * Photo de profil : seul domicile des règles d'envoi, pour l'API comme pour le back-office. L'image reçue n'est
 * jamais conservée telle quelle : elle est redressée, recadrée en carré et réencodée en WebP avant d'être enregistrée
 * (`store()` de `ConfiguredImageService`, décision 2026-11-24-image-service-abstrait).
 */
final class AvatarService extends ConfiguredImageService
{
    public const COLLECTION = 'avatar';

    /**
     * Ses réglages : `config('auth.avatar')`, `AUTH_AVATAR_*` (types, poids, qualité, conversions ; décision
     * 2026-11-24-avatar-photos-offre-conversions). La photo envoyée est gardée, en carré plafonné à `original_size` (décision 2026-11-24-partenaire-logo-sans-original).
     */
    protected static function configKey(): string
    {
        return 'auth.avatar';
    }

    /** Une seule photo : `singleFile()`, une nouvelle remplace l'ancienne. */
    protected static function collection(): string
    {
        return self::COLLECTION;
    }

    /** Un carré recadré au centre, comme ses conversions. */
    protected static function fit(): Fit
    {
        return Fit::Crop;
    }

    /** Toujours `avatar.webp` : une seule photo par compte. */
    protected static function fileName(?string $name): string
    {
        return 'avatar.webp';
    }

    /**
     * Règles de validation d'un fichier envoyé.
     *
     * @return list<mixed>
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
            // Au moins la taille enregistrée, au plus le garde-fou contre une image démesurée (décisions
            // 2026-11-24-image-regle-minimum-par-cadre et 2026-11-24-image-dimension-maximale).
            ...self::uploadRules(),
        ];
    }

    /**
     * Retire la photo du compte (fichier et conversions compris).
     */
    public function remove(User $user): void
    {
        $user->clearMediaCollection(self::COLLECTION);
        $user->unsetRelation('media');
    }
}
