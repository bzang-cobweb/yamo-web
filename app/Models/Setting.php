<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

/**
 * Réglages de l'application, édités dans le back-office (page Application) et
 * servis par `GET /api/v1/app` : description traduite ; « Appliquer le paiement »
 * (`payment_required`, décision 2026-11-19-contrat-paiement-visibilite-avis), non servi. Une seule ligne, obtenue
 * par `Setting::current()`. Le logo n'est pas un réglage : ses fichiers sont
 * livrés avec le code (`public/images/logo`, et dans l'app mobile).
 */
#[Fillable(['description', 'payment_required'])]
class Setting extends Model
{
    use HasTranslations;

    /** @var list<string> */
    public array $translatable = ['description'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'payment_required' => 'boolean',
        ];
    }

    /**
     * Le paiement est-il appliqué ? Alors une offre n'est visible des autres que
     * jusqu'à sa date d'expiration.
     */
    public static function paymentRequired(): bool
    {
        return (bool) self::query()->oldest('id')->value('payment_required');
    }

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
