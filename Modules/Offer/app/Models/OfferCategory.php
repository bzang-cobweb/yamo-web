<?php

namespace Modules\Offer\Models;

use App\Support\Locales;
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
use Spatie\Sluggable\HasTranslatableSlug;
use Spatie\Sluggable\SlugOptions;
use Spatie\Translatable\HasTranslations;

/**
 * Catégorie d'offres, dans un arbre (décision
 * 2026-11-18-offres-multilingues-categories-arbre) : `name` traduit, `slug` traduit, tiré du nom dans chaque
 * langue et unique parmi ses sœurs (décision 2026-11-25-categories-slug-traduit), `parent_id` (aucun pour une racine), `position` parmi ses sœurs.
 * Une catégorie qui a des enfants ne se supprime pas ; un parent ne peut pas
 * être la catégorie elle-même ni l'un de ses descendants.
 *
 * @property int|null $parent_id
 * @property array<string, string>|string $slug
 * @property int $position
 * @property-read OfferCategory|null $parent
 * @property-read Collection<int, OfferCategory> $children
 */
#[Fillable(['parent_id', 'name', 'slug', 'position'])]
#[UseFactory(OfferCategoryFactory::class)]
class OfferCategory extends Model
{
    /** @use HasFactory<OfferCategoryFactory> */
    use HasFactory, HasTranslatableSlug, HasTranslations;

    /** Séparateur des chemins : « Location › Meublée ». */
    public const PATH_SEPARATOR = ' › ';

    /** @var list<string> */
    public array $translatable = ['name', 'slug'];

    /**
     * Le slug de chaque langue, tiré de son nom à la création, unique parmi les catégories du même parent (décision
     * 2026-11-25-categories-slug-traduit) ; il ne change plus ensuite, même si le nom change (lecture seule au back-office).
     */
    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom('name')
            ->saveSlugsTo('slug')
            ->doNotGenerateSlugsOnUpdate()
            ->extraScope(fn (Builder $query): Builder => $this->parent_id === null ? $query->whereNull('parent_id') : $query->where('parent_id', $this->parent_id));
    }

    /**
     * Une catégorie par son slug dans une langue (par défaut, celle de l'application) et son parent.
     *
     * @param  Builder<OfferCategory>  $query
     * @return Builder<OfferCategory>
     */
    public function scopeWithSlug(Builder $query, string $slug, ?string $locale = null): Builder
    {
        return $query->where('slug->'.($locale ?? Locales::default()), $slug);
    }

    protected static function booted(): void
    {
        // Le slug ne change plus après la création (décision 2026-11-25-categories-slug-traduit) : un formulaire qui
        // l'envoie vide (lecture seule au back-office) ne l'efface pas.
        static::updating(function (OfferCategory $category): void {
            if ($category->isDirty('slug')) {
                $category->setRawAttributes([...$category->getAttributes(), 'slug' => $category->getRawOriginal('slug')]);
            }
        });

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
     * ordre). `$except` et ses descendants sont omis (choix d'un parent), ainsi que les racines `$withoutRoots` et
     * leurs enfants.
     *
     * @param  list<string>  $withoutRoots
     * @return array<string, string>
     */
    public static function options(?OfferCategory $except = null, array $withoutRoots = []): array
    {
        $all = self::query()->orderBy('position')->orderBy('id')->get();
        $byParent = $all->groupBy(fn (self $category): string => (string) $category->parent_id);
        $skip = $except === null ? [] : [(int) $except->getKey(), ...$except->descendantIds()];
        // Des racines écartées avec leurs enfants (les cases « Règles » et « Caractéristiques », décision 2026-11-25-cases-par-categories).
        foreach ($all->whereNull('parent_id')->filter(fn (self $root): bool => in_array($root->getTranslation('slug', Locales::default()), $withoutRoots, true)) as $root) {
            $skip = [...$skip, (int) $root->getKey(), ...$root->descendantIds()];
        }
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

    /**
     * Les enfants d'une racine (par son slug dans la langue par défaut), dans leur ordre : id => nom dans la langue
     * courante (décisions 2026-11-25-cases-par-categories et décision 2026-11-25-categories-slug-traduit).
     *
     * @return array<string, string>
     */
    public static function childrenOf(string $rootSlug): array
    {
        return self::query()
            ->where('parent_id', self::query()->whereNull('parent_id')->withSlug($rootSlug)->value('id'))
            ->orderBy('position')
            ->orderBy('id')
            ->get()
            ->mapWithKeys(fn (self $category): array => [(string) $category->getKey() => (string) $category->getTranslation('name', app()->getLocale())])
            ->all();
    }

    /** Le nom d'une racine dans la langue courante, ou `null` si elle n'existe pas. */
    public static function rootName(string $rootSlug): ?string
    {
        $root = self::query()->whereNull('parent_id')->withSlug($rootSlug)->first();

        return $root === null ? null : (string) $root->getTranslation('name', app()->getLocale());
    }
}
