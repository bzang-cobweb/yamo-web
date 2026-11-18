<?php

namespace Modules\Offer\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use LogicException;
use Modules\Offer\Database\Factories\OfferCategoryFactory;
use Spatie\Translatable\HasTranslations;

/**
 * Catégorie d'offres, dans un arbre (décision
 * 2026-11-18-offres-multilingues-categories-arbre) : `name` traduit, `slug`
 * unique, `parent_id` (aucun pour une racine), `position` parmi ses sœurs.
 * Une catégorie qui a des enfants ne se supprime pas ; un parent ne peut pas
 * être la catégorie elle-même ni l'un de ses descendants.
 *
 * @property int|null $parent_id
 * @property string $slug
 * @property int $position
 * @property-read OfferCategory|null $parent
 * @property-read Collection<int, OfferCategory> $children
 */
#[Fillable(['parent_id', 'name', 'slug', 'position'])]
#[UseFactory(OfferCategoryFactory::class)]
class OfferCategory extends Model
{
    /** @use HasFactory<OfferCategoryFactory> */
    use HasFactory, HasTranslations;

    /** Séparateur des chemins : « Location › Meublée ». */
    public const PATH_SEPARATOR = ' › ';

    /** @var list<string> */
    public array $translatable = ['name'];

    protected static function booted(): void
    {
        static::saving(function (OfferCategory $category): void {
            if ($category->parent_id !== null && $category->exists && in_array((int) $category->parent_id, [$category->getKey(), ...$category->descendantIds()], true)) {
                throw new LogicException(__('offer::offer.category_resource.cycle'));
            }
        });

        static::deleting(function (OfferCategory $category): void {
            if ($category->children()->exists()) {
                throw new LogicException(__('offer::offer.category_resource.has_children'));
            }
        });
    }

    /**
     * @return BelongsTo<OfferCategory, $this>
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    /**
     * Ses sous-catégories, dans l'ordre réglé.
     *
     * @return HasMany<OfferCategory, $this>
     */
    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('position')->orderBy('id');
    }

    /**
     * @return HasMany<Offer, $this>
     */
    public function offers(): HasMany
    {
        return $this->hasMany(Offer::class, 'category_id');
    }

    /**
     * Les catégories racines.
     *
     * @param  Builder<OfferCategory>  $query
     */
    public function scopeRoots(Builder $query): void
    {
        $query->whereNull('parent_id');
    }

    /**
     * Identifiants de tous ses descendants.
     *
     * @return list<int>
     */
    public function descendantIds(): array
    {
        $ids = [];
        $level = [(int) $this->getKey()];

        while ($level !== []) {
            $level = self::query()->whereIn('parent_id', $level)->pluck('id')->map(fn ($id): int => (int) $id)->all();
            $level = array_values(array_diff($level, $ids));
            $ids = [...$ids, ...$level];
        }

        return $ids;
    }

    /**
     * « Location › Meublée » dans la langue courante.
     */
    public function path(): string
    {
        $names = [];
        $category = $this;
        $seen = [];

        while ($category !== null && ! in_array($category->getKey(), $seen, true)) {
            $seen[] = $category->getKey();
            array_unshift($names, (string) $category->getTranslation('name', app()->getLocale()));
            $category = $category->parent;
        }

        return implode(self::PATH_SEPARATOR, $names);
    }

    /**
     * id => chemin, dans l'ordre de l'arbre (parent, puis ses enfants dans leur
     * ordre). `$except` et ses descendants sont omis (choix d'un parent).
     *
     * @return array<string, string>
     */
    public static function options(?OfferCategory $except = null): array
    {
        $all = self::query()->orderBy('position')->orderBy('id')->get();
        $byParent = $all->groupBy(fn (self $category): string => (string) $category->parent_id);
        $skip = $except === null ? [] : [(int) $except->getKey(), ...$except->descendantIds()];
        $options = [];

        $walk = function (string $parent, array $names) use (&$walk, &$options, $byParent, $skip): void {
            foreach ($byParent->get($parent, []) as $category) {
                if (in_array((int) $category->getKey(), $skip, true)) {
                    continue;
                }

                $path = [...$names, (string) $category->getTranslation('name', app()->getLocale())];
                $options[(string) $category->getKey()] = implode(self::PATH_SEPARATOR, $path);
                $walk((string) $category->getKey(), $path);
            }
        };
        $walk('', []);

        return $options;
    }
}
