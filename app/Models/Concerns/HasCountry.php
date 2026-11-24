<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Location\Models\Country;
use Modules\Location\Support\CurrentCountry;

/**
 * Ce qui se publie dans un pays (colonne `country_id`), une offre ou un partenaire (décision
 * 2026-11-24-visibilite-pays-paiement) : son pays, et ceux du pays donné, sinon du pays courant, en scope pour les
 * requêtes et en méthode pour un modèle chargé. Le modèle garde `country_id` dans ses attributs remplissables.
 *
 * @property int|null $country_id
 * @property-read Country|null $country
 */
trait HasCountry
{
    /** @return BelongsTo<Country, $this> */
    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    /**
     * Ceux du pays `$country`, sinon du pays courant.
     *
     * @param  Builder<static>  $query
     */
    public function scopeVisibleCountry(Builder $query, Country|int|null $country = null): void
    {
        $query->where($query->qualifyColumn('country_id'), self::countryKey($country));
    }

    /** Du pays `$country`, sinon du pays courant : la règle de `scopeVisibleCountry()`, pour un modèle chargé. */
    public function isInCountry(Country|int|null $country = null): bool
    {
        return (int) $this->getAttribute('country_id') === self::countryKey($country);
    }

    /** L'identifiant du pays `$country`, sinon du pays courant. */
    private static function countryKey(Country|int|null $country): int
    {
        if ($country instanceof Country) {
            return (int) $country->getKey();
        }

        return $country ?? (int) CurrentCountry::require()->getKey();
    }
}
