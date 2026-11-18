<?php

namespace Modules\Offer\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Offer\Database\Factories\OfferCategoryFactory;
use Spatie\Translatable\HasTranslations;

/**
 * Catégorie d'offres : `name` traduit, `slug` unique, `position` d'affichage.
 *
 * @property string $slug
 * @property int $position
 */
#[Fillable(['name', 'slug', 'position'])]
#[UseFactory(OfferCategoryFactory::class)]
class OfferCategory extends Model
{
    /** @use HasFactory<OfferCategoryFactory> */
    use HasFactory, HasTranslations;

    /** @var list<string> */
    public array $translatable = ['name'];

    /**
     * @return HasMany<Offer, $this>
     */
    public function offers(): HasMany
    {
        return $this->hasMany(Offer::class, 'category_id');
    }

    /**
     * id => nom dans la langue courante, dans l'ordre d'affichage.
     *
     * @return array<string, string>
     */
    public static function options(): array
    {
        return self::query()->orderBy('position')->orderBy('id')->get()
            ->mapWithKeys(fn (self $category): array => [(string) $category->getKey() => (string) $category->getTranslation('name', app()->getLocale())])
            ->all();
    }
}
