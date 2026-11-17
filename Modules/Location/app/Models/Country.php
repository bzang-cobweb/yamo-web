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
use Illuminate\Support\Facades\App;
use Modules\Location\Database\Factories\CountryFactory;
use Spatie\Translatable\HasTranslations;

/**
 * Un pays : `code` ISO-2, `name` traduit (fr, en), `native_name` dans la langue du
 * pays, `dial_code` avec son « + » (`+41`).
 *
 * @property string $code
 * @property string $native_name
 * @property string $dial_code
 * @property bool $active
 */
#[Fillable(['code', 'name', 'native_name', 'dial_code', 'active'])]
#[UseFactory(CountryFactory::class)]
class Country extends Model
{
    /** @use HasFactory<CountryFactory> */
    use HasFactory, HasTranslations;

    /** @var list<string> */
    public array $translatable = ['name'];

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
     * Ses adresses (décision 2026-11-18-adresses-liste-generique).
     *
     * @return HasMany<Address, $this>
     */
    public function addresses(): HasMany
    {
        return $this->hasMany(Address::class);
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
}
