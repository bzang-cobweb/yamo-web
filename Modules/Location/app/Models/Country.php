<?php

namespace Modules\Location\Models;

use Collator;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Support\Facades\App;
use Modules\Location\Database\Factories\CountryFactory;
use Spatie\Translatable\HasTranslations;

/**
 * Un pays : `code` ISO-2, `name` traduit (fr, en), `native_name` dans la langue du
 * pays, `dial_code` avec son « + » (`+41`), `currency` ISO 4217 (`XAF`), qui est
 * celle des offres de ce pays (décision 2026-11-18-espace-pays).
 *
 * @property string $code
 * @property string $native_name
 * @property array<string, string>|null $region_label le nom de ses régions, traduit (décision 2026-11-25-regions-par-pays)
 * @property string $dial_code
 * @property string|null $currency
 * @property bool $active
 */
#[Fillable(['code', 'name', 'native_name', 'region_label', 'dial_code', 'currency', 'active'])]
#[UseFactory(CountryFactory::class)]
class Country extends Model
{
    /** @use HasFactory<CountryFactory> */
    use HasFactory, HasTranslations;

    /** @var list<string> */
    public array $translatable = ['name', 'region_label'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'active' => 'boolean',
        ];
    }

    /**
     * Ses régions, dans l'ordre réglé dans le back-office.
     *
     * @return HasMany<Region, $this>
     */
    public function regions(): HasMany
    {
        return $this->hasMany(Region::class)->orderBy('position')->orderBy('id');
    }

    /**
     * Ses adresses (décision 2026-11-18-adresses-liste-generique) : celles de ses régions (décision
     * 2026-11-25-adresse-sans-pays).
     *
     * @return HasManyThrough<Address, Region, $this>
     */
    public function addresses(): HasManyThrough
    {
        return $this->hasManyThrough(Address::class, Region::class);
    }

    /**
     * Pays actifs, par libellé dans la langue courante (tri selon la langue).
     *
     * @return array<string, string>
     */
    public static function options(): array
    {
        $collator = new Collator(App::getLocale());

        return static::query()->active()->get()
            ->mapWithKeys(fn (self $country): array => [(string) $country->getKey() => $country->label()])
            ->sort(fn (string $a, string $b): int => (int) $collator->compare($a, $b))
            ->all();
    }

    /**
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function active(Builder $query): void
    {
        $query->where('active', true);
    }

    /**
     * Le nom de ses régions dans la langue courante : « Canton », « District », « Région »… ; sans réglage, « Région »
     * (décision 2026-11-25-regions-par-pays).
     */
    public function regionLabel(): string
    {
        $label = trim((string) $this->getTranslation('region_label', app()->getLocale()));

        return $label === '' ? (string) __('location::address.region') : $label;
    }

    /**
     * Nom dans la langue courante, suivi du nom natif s'il diffère :
     * « Allemagne (Deutschland) », « France ».
     */
    public function label(): string
    {
        $name = (string) $this->getTranslation('name', app()->getLocale());

        if ($this->native_name === '' || $this->native_name === $name) {
            return $name;
        }

        return $name.' ('.$this->native_name.')';
    }

    /**
     * Drapeau en emoji, tiré du code (CM → 🇨🇲).
     */
    public function flag(): string
    {
        return implode('', array_map(fn (string $letter): string => mb_chr(0x1F1E6 + ord($letter) - ord('A')), str_split(strtoupper($this->code))));
    }
}
