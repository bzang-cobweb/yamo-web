<?php

namespace Modules\Location\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;
use Modules\Auth\Models\User;
use Modules\Location\Database\Factories\AddressFactory;

/**
 * Une adresse d'un pays, qui n'existe qu'une fois et que plusieurs comptes
 * peuvent partager (pivot `address_user`, décision
 * 2026-11-18-adresses-liste-generique). Deux adresses sont la même si pays,
 * rue, NPA et ville sont identiques, sans tenir compte de la casse ni des
 * espaces : c'est ce que dit l'empreinte `fingerprint`, calculée à chaque
 * enregistrement. Les espaces en trop sont retirés.
 *
 * @property int $country_id
 * @property string $street
 * @property string $zip
 * @property string $city
 * @property string|null $latitude
 * @property string|null $longitude
 * @property string $fingerprint
 */
#[Fillable(['country_id', 'street', 'zip', 'city', 'latitude', 'longitude'])]
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
     * @return BelongsTo<Country, $this>
     */
    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    /**
     * Les comptes rattachés à cette adresse.
     *
     * @return BelongsToMany<User, $this>
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)->withTimestamps();
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
