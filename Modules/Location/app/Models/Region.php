<?php

namespace Modules\Location\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Location\Database\Factories\RegionFactory;
use Spatie\Translatable\HasTranslations;

/**
 * Une région d'un pays (canton, État…) : `name` traduit (fr, en), `position`
 * dans son pays, réglée dans le back-office. Une région créée sans position se
 * place à la fin de son pays.
 *
 * @property int $country_id
 * @property bool $active
 * @property int $position
 */
#[Fillable(['country_id', 'name', 'active', 'position'])]
#[UseFactory(RegionFactory::class)]
class Region extends Model
{
    /** @use HasFactory<RegionFactory> */
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
            'position' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $region): void {
            if ($region->position === null) {
                $region->position = (int) static::query()->where('country_id', $region->country_id)->max('position') + 1;
            }
        });
    }

    /**
     * @return BelongsTo<Country, $this>
     */
    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    /**
     * Les régions actives du pays, dans l'ordre du back-office : id => nom dans la langue de l'app (décision
     * 2026-11-25-region-de-l-adresse).
     *
     * @return array<string, string>
     */
    public static function optionsIn(int|string $country): array
    {
        return static::query()->active()->where('country_id', $country)->orderBy('position')->orderBy('id')->get()
            ->mapWithKeys(fn (self $region): array => [(string) $region->getKey() => trim((string) $region->getTranslation('name', app()->getLocale()))])
            ->all();
    }

    /**
     * Les régions actives des pays actifs, avec le nom du pays (« Littoral (Cameroun) ») : id => libellé, par pays puis
     * dans l'ordre du back-office (décision 2026-11-25-adresse-sans-pays).
     *
     * @return array<string, string>
     */
    public static function optionsWithCountry(): array
    {
        return static::query()->active()->whereHas('country', fn (Builder $country) => $country->where('active', true))
            ->with('country')->orderBy('country_id')->orderBy('position')->orderBy('id')->get()
            ->mapWithKeys(fn (self $region): array => [(string) $region->getKey() => trim((string) $region->getTranslation('name', app()->getLocale())).' ('.$region->country?->label().')'])
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
}
