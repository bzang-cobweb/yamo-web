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
 * Une région d'un pays (canton, État…) : `name` traduit (fr, en).
 *
 * @property int $country_id
 * @property bool $active
 */
#[Fillable(['country_id', 'name', 'active'])]
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
        ];
    }

    /**
     * @return BelongsTo<Country, $this>
     */
    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
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
