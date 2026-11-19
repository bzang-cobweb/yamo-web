<?php

namespace Modules\Offer\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Offer\Database\Factories\OfferInformationFactory;
use Spatie\Translatable\HasTranslations;

/**
 * Un bloc d'informations d'une offre (décision 2026-11-19-contrat-offer-informations) :
 * un titre et une description, traduits, à sa place (`position`) parmi les blocs de
 * l'offre. Sur la fiche, chaque bloc est une section repliable.
 *
 * @property int $offer_id
 * @property string $title
 * @property string $description
 * @property int $position
 * @property-read Offer $offer
 */
#[Table('offer_informations')]
#[Fillable(['offer_id', 'title', 'description', 'position'])]
#[UseFactory(OfferInformationFactory::class)]
class OfferInformation extends Model
{
    /** @use HasFactory<OfferInformationFactory> */
    use HasFactory;

    use HasTranslations { normalizeLocale as translatableLocale; }

    /** Longueurs maximales et nombre maximal de blocs par offre. */
    public const TITLE_MAX_LENGTH = 100;

    public const DESCRIPTION_MAX_LENGTH = 2000;

    public const MAX_PER_OFFER = 10;

    /** @var list<string> */
    public array $translatable = ['title', 'description'];

    /**
     * Un texte manquant s'affiche dans la langue d'origine de l'offre (décision
     * 2026-11-19-contrat-langue-saisie-allemand).
     */
    protected function normalizeLocale(string $key, string $locale, bool $useFallbackLocale): string
    {
        return OriginalLocale::pick($this->getTranslatedLocales($key), $locale, $useFallbackLocale ? $this->offer?->locale : null)
            ?? $this->translatableLocale($key, $locale, $useFallbackLocale);
    }

    /**
     * @return BelongsTo<Offer, $this>
     */
    public function offer(): BelongsTo
    {
        return $this->belongsTo(Offer::class);
    }
}
