<?php

namespace Modules\Location\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;
use Illuminate\Support\Str;
use Modules\Location\Database\Factories\AddressFactory;

/**
 * Une adresse d'un pays, qui n'existe qu'une fois et que plusieurs comptes
 * peuvent viser (module Auth : décisions
 * 2026-11-18-adresses-liste-generique, 2026-11-18-adresse-du-compte-modele et
 * 2026-11-18-useraddress-dans-auth). Deux adresses sont la même si région,
 * rue, NPA et ville sont identiques, sans tenir compte de la casse ni des
 * espaces : c'est ce que dit l'empreinte `fingerprint`, calculée à chaque
 * enregistrement. Les espaces en trop sont retirés. Son pays est celui de sa
 * région : l'adresse n'a pas de pays à elle (décision 2026-11-25-adresse-sans-pays).
 *
 * @property string $street
 * @property string $zip
 * @property string $city
 * @property int $region_id sa région, qui donne son pays (décisions 2026-11-25-region-de-l-adresse et
 *                          2026-11-25-adresse-sans-pays)
 * @property string|null $latitude
 * @property string|null $longitude
 * @property string $fingerprint
 */
#[Fillable(['region_id', 'street', 'zip', 'city', 'latitude', 'longitude'])]
#[Hidden(['fingerprint'])]
#[UseFactory(AddressFactory::class)]
class Address extends Model
{
    /** @use HasFactory<AddressFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (self $address): void {
            $address->street = Str::squish($address->street);
            $address->zip = Str::squish($address->zip);
            $address->city = Str::squish($address->city);
            $address->fingerprint = self::fingerprint($address->street, $address->zip, $address->city);
        });
    }

    /**
     * Empreinte d'une saisie : rue, NPA et ville sans casse ni espaces en trop.
     */
    public static function fingerprint(string $street, string $zip, string $city): string
    {
        return sha1(implode("\n", array_map(
            fn (string $part): string => Str::lower(Str::squish($part)),
            [$street, $zip, $city],
        )));
    }

    /**
     * Son pays : celui de sa région (décision 2026-11-25-adresse-sans-pays).
     *
     * @return HasOneThrough<Country, Region, $this>
     */
    public function country(): HasOneThrough
    {
        return $this->hasOneThrough(Country::class, Region::class, 'id', 'id', 'region_id', 'country_id');
    }

    /**
     * Les adresses d'un pays : celles de ses régions.
     *
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function inCountry(Builder $query, int|string $country): void
    {
        $query->whereIn($query->qualifyColumn('region_id'), Region::query()->select('id')->where('country_id', $country));
    }

    /**
     * Sa région (décision 2026-11-25-region-de-l-adresse).
     *
     * @return BelongsTo<Region, $this>
     */
    public function region(): BelongsTo
    {
        return $this->belongsTo(Region::class);
    }

    /**
     * « Rue du Lac 4 — 1200 Genève » : libellé d'une suggestion, qui distingue deux
     * adresses de même rue.
     */
    public function suggestionLabel(): string
    {
        return $this->street.' — '.trim($this->zip.' '.$this->city);
    }

    /**
     * « Rue du Lac 4 ».
     */
    public function title(): string
    {
        return $this->street;
    }

    /**
     * « 1200 Genève, Suisse ».
     */
    public function subtitle(): string
    {
        $country = $this->country?->getTranslation('name', app()->getLocale());

        return trim($this->zip.' '.$this->city).($country ? ', '.$country : '');
    }
}
