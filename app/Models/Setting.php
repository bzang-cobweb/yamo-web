<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

/**
 * Réglages de l'application, édités dans le back-office (page Application) et
 * servis par `GET /api/v1/app` : description traduite. Une seule ligne, obtenue
 * par `Setting::current()`. Le logo n'est pas un réglage : ses fichiers sont
 * livrés avec le code (`public/images/logo`, et dans l'app mobile).
 */
#[Fillable(['description'])]
class Setting extends Model
{
    use HasTranslations;

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
     * Description dans la langue courante (repli sur la langue par défaut), ou
     * `null` si aucune n'est saisie.
     */
    public function localizedDescription(): ?string
    {
        $description = (string) $this->getTranslation('description', app()->getLocale());

        return $description === '' ? null : $description;
    }
}
