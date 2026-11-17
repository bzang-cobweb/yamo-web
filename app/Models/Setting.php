<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\Translatable\HasTranslations;

/**
 * Réglages de l'application, édités dans le back-office (page Application) et
 * servis par `GET /api/v1/app` : logo (média) et description traduite. Une seule
 * ligne, obtenue par `Setting::current()`.
 */
#[Fillable(['description'])]
class Setting extends Model implements HasMedia
{
    use HasTranslations, InteractsWithMedia;

    public const LOGO = 'logo';

    /** @var list<string> */
    public array $translatable = ['description'];

    /**
     * La ligne des réglages, créée vide au premier besoin.
     */
    public static function current(): self
    {
        $setting = self::query()->oldest('id')->firstOrCreate();
        // Une lecture, pas une création : une réponse d'API reste un 200.
        $setting->wasRecentlyCreated = false;

        return $setting;
    }

    /**
     * Logo : un seul fichier, une nouvelle image remplace l'ancienne.
     */
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection(self::LOGO)
            ->singleFile()
            ->acceptsMimeTypes(['image/png', 'image/jpeg', 'image/webp', 'image/svg+xml'])
            ->useDisk('public');
    }

    /**
     * Adresse du logo, ou `null` sans logo.
     */
    public function logoUrl(): ?string
    {
        $url = $this->getFirstMediaUrl(self::LOGO);

        return $url === '' ? null : $url;
    }

    /**
     * Description dans la langue courante (repli sur la langue par défaut), ou
     * `null` si aucune n'est saisie.
     */
    public function localizedDescription(): ?string
    {
        $description = (string) $this->getTranslation('description', app()->getLocale());

        return $description === '' ? null : $description;
    }
}
