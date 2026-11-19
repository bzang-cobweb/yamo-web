<?php

namespace Modules\Offer\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Location\Models\Address;
use Modules\Offer\Database\Factories\OfferAddressFactory;
use Spatie\Translatable\HasTranslations;

/**
 * L'adresse d'une offre (décision 2026-11-19-contrat-adresse-offre-table) : l'adresse
 * partagée du pays (`Address`, module Location) et l'indication propre à l'offre
 * (« derrière le marché, portail bleu »), traduite (décision 2026-11-19-indication-traduisible) ;
 * un texte manquant s'affiche dans la langue d'origine de l'offre. Une offre en a au plus une ;
 * deux offres au même endroit partagent l'`Address`, chacune a son indication.
 *
 * @property int $offer_id
 * @property int $address_id
 * @property string|null $indication
 * @property-read Offer $offer
 * @property-read Address $address
 */
#[Table('offer_addresses')]
#[Fillable(['offer_id', 'address_id', 'indication'])]
#[UseFactory(OfferAddressFactory::class)]
class OfferAddress extends Model
{
    /** @use HasFactory<OfferAddressFactory> */
    use HasFactory;

    use HasTranslations { normalizeLocale as translatableLocale; }

    /** @var list<string> */
    public array $translatable = ['indication'];

    /** Longueur maximale de l'indication. */
    public const INDICATION_MAX_LENGTH = 500;

    /**
     * Un texte manquant s'affiche dans la langue d'origine de l'offre.
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

    /**
     * @return BelongsTo<Address, $this>
     */
    public function address(): BelongsTo
    {
        return $this->belongsTo(Address::class);
    }
}
