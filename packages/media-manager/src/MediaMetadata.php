<?php

namespace Bza\MediaManager;

use Illuminate\Support\Str;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * The descriptive metadata of a file, kept in its custom properties under
 * "metadata": the alternative text, title and caption per locale, the credit,
 * and free key/value pairs. The media attached to the other models get
 * metadata too: only their files stay read-only in the manager.
 *
 *     ['alt' => ['fr' => '…', 'en' => '…'], 'title' => [...], 'caption' => [...],
 *      'credit' => '© Studio', 'extra' => ['source' => 'Shooting 2026']]
 */
class MediaMetadata
{
    /** The texts written once per locale. */
    public const TRANSLATED = ['alt', 'title', 'caption'];

    public const PROPERTY = 'metadata';

    /**
     * @return list<string>
     */
    public function locales(): array
    {
        $locales = config('media-manager.locales') ?? config('app.supported_locales') ?? [config('app.locale')];

        return array_values(array_unique(array_filter(array_map('strval', (array) $locales))));
    }

    /**
     * Every field, empty ones included.
     *
     * @return array{alt: array<string, string>, title: array<string, string>, caption: array<string, string>, credit: string, extra: array<string, string>}
     */
    public function get(Media $media): array
    {
        $stored = (array) $media->getCustomProperty(self::PROPERTY, []);
        $metadata = [];

        foreach (self::TRANSLATED as $field) {
            foreach ($this->locales() as $locale) {
                $metadata[$field][$locale] = (string) ($stored[$field][$locale] ?? '');
            }
        }

        $metadata['credit'] = (string) ($stored['credit'] ?? '');
        $metadata['extra'] = array_map('strval', (array) ($stored['extra'] ?? []));

        return $metadata;
    }

    /**
     * One translated text in the locale, else the fallback locale, else the
     * first filled one.
     */
    public function text(Media $media, string $field, ?string $locale = null): ?string
    {
        $texts = array_filter($this->get($media)[$field] ?? [], fn (string $text): bool => $text !== '');

        return $texts[$locale ?? app()->getLocale()] ?? $texts[config('app.fallback_locale')] ?? (reset($texts) ?: null);
    }

    /**
     * Replaces the metadata of a media, the manager's or another model's. Empty texts and
     * pairs are dropped; a key is trimmed and must be unique.
     *
     * @param  array<string, mixed>  $metadata
     */
    public function update(Media $media, array $metadata): Media
    {
        $clean = [];

        foreach (self::TRANSLATED as $field) {
            foreach ($this->locales() as $locale) {
                if (($text = $this->cleanText($metadata[$field][$locale] ?? null)) !== null) {
                    $clean[$field][$locale] = $text;
                }
            }
        }

        if (($credit = $this->cleanText($metadata['credit'] ?? null)) !== null) {
            $clean['credit'] = $credit;
        }

        foreach ((array) ($metadata['extra'] ?? []) as $key => $value) {
            $key = trim((string) $key);

            if ($key === '' || ($value = $this->cleanText($value)) === null) {
                continue;
            }

            if (mb_strlen($key) > 100) {
                throw MediaManagerException::because('metadata_key_invalid');
            }

            $clean['extra'][$key] = $value;
        }

        if ($clean === []) {
            $media->forgetCustomProperty(self::PROPERTY);
        } else {
            $media->setCustomProperty(self::PROPERTY, $clean);
        }

        $media->save();

        return $media;
    }

    /**
     * What the file itself tells: type, size, image dimensions, dates.
     *
     * @return array{mime_type: string|null, size: int, width: int|null, height: int|null, created_at: mixed, updated_at: mixed, uuid: string|null}
     */
    public function technical(Media $media): array
    {
        $width = $height = null;

        if (str_starts_with((string) $media->mime_type, 'image/')) {
            // Only readable on a local disk; a remote file is not downloaded for this.
            try {
                $size = @getimagesize($media->getPath());
                [$width, $height] = $size === false ? [null, null] : [$size[0], $size[1]];
            } catch (\Throwable) {
                // No dimensions.
            }
        }

        return [
            'mime_type' => $media->mime_type,
            'size' => (int) $media->size,
            'width' => $width,
            'height' => $height,
            'created_at' => $media->created_at,
            'updated_at' => $media->updated_at,
            'uuid' => $media->uuid,
        ];
    }

    private function cleanText(mixed $value): ?string
    {
        $text = trim((string) $value);

        return $text === '' ? null : Str::limit($text, 1000, '');
    }
}
