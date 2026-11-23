<?php

namespace App\Support\Media;

/**
 * Les réglages d'une image envoyée, lus dans la configuration de son module (décision
 * 2026-11-24-image-service-abstrait) : un seul domicile pour leur lecture. Chaque service d'image (`OfferPhotoService`,
 * `PartnerLogoService`) l'étend et ne déclare que sa clé (`offer.photos`, `partner.logo`) ; ce qui lui est propre
 * (cadre d'une photo, minimum à l'envoi d'un logo) reste chez lui.
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
     * La taille de l'image enregistrée, en pixels : le plafond de l'original quand il est gardé (`original_size`, le
     * logo d'un partenaire), sinon la plus grande des conversions, dont elle est la source (les photos d'une offre).
     */
    public static function storedSize(): int
    {
        $original = (int) static::setting('original_size', 0);
        if ($original > 0) {
            return $original;
        }

        $sizes = static::conversions();
        if ($sizes === []) {
            return 1024;
        }

        return max($sizes);
    }
}
