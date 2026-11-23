<?php

namespace App\Support\Media;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Dimensions;
use Modules\Form\Models\FormUpload;
use Modules\Form\Rules\MinImageSide;
use Spatie\Image\Enums\Fit;
use Spatie\Image\Image;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * Une image envoyée : ses réglages, lus dans la configuration de son module, sa taille minimale à l'envoi et son
 * enregistrement (décisions 2026-11-24-image-service-abstrait et 2026-11-24-image-taille-minimale-enregistree) ; un seul
 * domicile pour les trois. Chaque service d'image (`OfferPhotoService`, `PartnerLogoService`, `AvatarService`)
 * l'étend et ne déclare que sa clé (`offer.photos`, `partner.logo`, `auth.avatar`) et sa collection ; ce qui lui est
 * propre (cadre d'une photo, envoi depuis l'app) reste chez lui.
 *
 * Clés lues sous `configKey()` : `mime_types`, `max_upload_kb`, `max_files` (1 par défaut), `quality`,
 * `original_size` (facultative) et `conversions` (nom => taille, en pixels).
 */
abstract class ConfiguredImageService
{
    /** La clé de configuration de l'image (`offer.photos`, `partner.logo`). */
    abstract protected static function configKey(): string;

    /** Un réglage de l'image : `config('<clé>.<nom>')`. */
    protected static function setting(string $name, mixed $default = null): mixed
    {
        return config(static::configKey().'.'.$name, $default);
    }

    /**
     * Les types de fichier acceptés à l'envoi, avant optimisation.
     *
     * @return list<string>
     */
    public static function acceptedMimeTypes(): array
    {
        return array_values((array) static::setting('mime_types', []));
    }

    /** La taille maximale d'un fichier envoyé, en kilo-octets. */
    public static function maxUploadKb(): int
    {
        return (int) static::setting('max_upload_kb');
    }

    /** Le nombre maximal de fichiers ; un seul sans réglage. */
    public static function maxFiles(): int
    {
        return (int) static::setting('max_files', 1);
    }

    /** La qualité du WebP enregistré et de ses conversions, de 1 à 100. */
    public static function quality(): int
    {
        return (int) static::setting('quality');
    }

    /**
     * Les conversions, une par endroit où l'image s'affiche : nom => taille, en pixels.
     *
     * @return array<string, int>
     */
    public static function conversions(): array
    {
        return array_map('intval', (array) static::setting('conversions', []));
    }

    /** La taille de la conversion `$name`, en pixels ; 0 si elle n'existe pas. */
    public static function conversionSize(string $name): int
    {
        return static::conversions()[$name] ?? 0;
    }

    /**
     * La taille minimale d'une image envoyée, en pixels : la taille enregistrée (`storedSize()`), pour que ni
     * l'image enregistrée, qui peut s'ouvrir en grand, ni aucune conversion ne soit un agrandissement (décision
     * 2026-11-24-image-taille-minimale-enregistree).
     */
    public static function minUploadSize(): int
    {
        return static::storedSize();
    }

    /** La taille de la plus grande des conversions, en pixels ; 0 sans conversion. */
    public static function largestConversion(): int
    {
        $sizes = static::conversions();
        if ($sizes === []) {
            return 0;
        }

        return max($sizes);
    }

    /**
     * Les règles d'un fichier envoyé propres à l'image, au-delà du type et du poids : le minimum à l'envoi, tiré du
     * cadre de l'image enregistrée (`bounds(minUploadSize())`) et de la façon d'y tenir (`fit()`), pour qu'elle ne
     * soit jamais agrandie (décision 2026-11-24-image-regle-minimum-par-cadre) :
     * - recadrée (`Fit::Crop`, la photo de profil) : les deux côtés au moins ceux du cadre (`dimensions`) ;
     * - entière dans un cadre plus haut que large (les photos d'une offre, bornées par la largeur) : la largeur ;
     * - entière dans un cadre plus large que haut : la hauteur ;
     * - entière dans un carré (le logo) : le plus grand côté (`MinImageSide`, que `dimensions` ne sait pas dire).
     * Pour l'app (`Field::uploadRules()`), le back-office (`->rules()`) et toute validation écrite à la main.
     *
     * @return list<ValidationRule|Dimensions>
     */
    public static function uploadRules(): array
    {
        $size = static::minUploadSize();
        if ($size <= 0) {
            return [];
        }

        [$width, $height] = static::bounds($size);
        if (static::fit() === Fit::Crop) {
            return [Rule::dimensions()->minWidth($width)->minHeight($height)];
        }
        if ($width === $height) {
            return [new MinImageSide($width)];
        }
        if ($height > $width) {
            return [Rule::dimensions()->minWidth($width)];
        }

        return [Rule::dimensions()->minHeight($height)];
    }

    /**
     * La taille de l'image enregistrée, en pixels : le plafond de l'original quand il est gardé (`original_size`, la
     * photo de profil), sinon la plus grande des conversions, dont elle est la source (photos d'une offre, logo).
     */
    public static function storedSize(): int
    {
        $original = (int) static::setting('original_size', 0);
        if ($original > 0) {
            return $original;
        }

        $largest = static::largestConversion();
        if ($largest === 0) {
            return 1024;
        }

        return $largest;
    }

    /** La collection de médias de l'image (`Offer::PHOTOS`, `Partner::LOGO`). */
    abstract protected static function collection(): string;

    /** Le nom donné au média enregistré ; celui de la collection par défaut. */
    protected static function mediaName(): string
    {
        return static::collection();
    }

    /**
     * Le cadre d'une taille `$size` (largeur, hauteur), dans lequel l'image tient, proportions gardées : un carré par
     * défaut (le plus grand côté) ; un service peut le redéfinir (une photo bornée par sa largeur).
     *
     * @return array{0: int, 1: int}
     */
    public static function bounds(int $size): array
    {
        return [$size, $size];
    }

    /**
     * Comment l'image tient dans son cadre : entière, proportions gardées (`Fit::Max`) par défaut ; recadrée pour le
     * remplir (`Fit::Crop`) quand le format est imposé (une photo de profil carrée).
     */
    protected static function fit(): Fit
    {
        return Fit::Max;
    }

    /** Le nom du fichier enregistré : `$name` (l'identifiant de l'envoi) ou un uuid, en `.webp`. */
    protected static function fileName(?string $name): string
    {
        return ($name ?? (string) Str::uuid()).'.webp';
    }

    /**
     * Le fichier `$sourcePath` devient une image de `$model`, dans sa collection : redressée (EXIF), en WebP, tenue
     * dans le cadre de la taille enregistrée (`bounds(storedSize())`), jamais agrandie ; le fichier envoyé tel quel
     * n'est pas gardé (décision 2026-11-24-image-service-abstrait). Le même pour l'app, le back-office et les données
     * de démo ; une collection `singleFile()` remplace l'image précédente.
     */
    public function store(HasMedia $model, string $sourcePath, ?string $name = null): Media
    {
        $base = (string) tempnam(sys_get_temp_dir(), 'image');
        $optimised = $base.'.webp';
        [$width, $height] = static::bounds(static::storedSize());

        try {
            $image = Image::load($sourcePath)->orientation();
            // Recadrer remplit le cadre, quitte à agrandir : le cadre est réduit à ce que l'image couvre, aux mêmes
            // proportions, pour qu'elle ne le soit jamais (décision 2026-11-24-partenaire-logo-sans-original).
            if (static::fit() === Fit::Crop) {
                $scale = min(1, $image->getWidth() / $width, $image->getHeight() / $height);
                $width = max(1, (int) floor($width * $scale));
                $height = max(1, (int) floor($height * $scale));
            }

            $image
                ->fit(static::fit(), $width, $height)
                ->quality(static::quality())
                ->format('webp')
                ->save($optimised);

            $media = $model->addMedia($optimised)
                ->usingName(static::mediaName())
                ->usingFileName(static::fileName($name))
                ->toMediaCollection(static::collection());
        } finally {
            foreach ([$base, $optimised] as $path) {
                if (is_file($path)) {
                    unlink($path);
                }
            }
        }

        // Sans quoi le modèle garde en mémoire ses anciens médias, et `getFirstMedia()` l'image remplacée.
        if ($model instanceof Model) {
            $model->unsetRelation('media');
        }

        return $media;
    }

    /**
     * Un fichier envoyé depuis un formulaire de l'app, en attente, devient une image de `$model` (`store()`) ; il est
     * ensuite retiré.
     */
    protected function storeUpload(HasMedia $model, FormUpload $upload): Media
    {
        $media = $this->store($model, $upload->absolutePath(), $upload->uuid);
        $upload->discard();

        return $media;
    }
}
