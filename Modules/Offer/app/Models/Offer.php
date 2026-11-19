<?php

namespace Modules\Offer\Models;

use App\Support\Locales;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Modules\Auth\Models\User;
use Modules\Location\Models\Address;
use Modules\Location\Models\Country;
use Modules\Offer\Database\Factories\OfferFactory;
use Modules\Offer\Enums\OfferStatusEnum;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\Translatable\HasTranslations;

/**
 * Une offre (décision 2026-11-18-offres-et-domaines) : ce qu'un compte propose,
 * quel que soit le domaine du projet. Les champs communs sont des colonnes ; les
 * attributs propres au domaine (`Domain::attributes()`) sont dans `details` ; un
 * attribut traduisible y est rangé par langue (`{"note": {"fr": …, "en": …}}`).
 *
 * @property int $user_id
 * @property int|null $country_id
 * @property int|null $category_id
 * @property int|null $address_id
 * @property string $title
 * @property string|null $description
 * @property OfferStatusEnum $status
 * @property string|null $price
 * @property string|null $currency
 * @property array<string, mixed>|null $details
 * @property Carbon|null $published_at
 * @property Carbon|null $featured_until en vedette jusqu'à cette date (décision 2026-11-19-contrat-vedette-favoris)
 * @property Carbon|null $paid_until payée jusqu'à cette date (décision 2026-11-19-acces-avis-backoffice)
 * @property bool|null $is_favorite chargé par `withFavoriteOf()`
 * @property-read User $user
 * @property-read Country|null $country
 * @property-read OfferCategory|null $category
 * @property-read Address|null $address
 * @property float|string|null $reviews_avg_rating moyenne des notes, chargée par `withReviewSummary()`
 * @property int|null $reviews_count nombre d'avis, chargé par `withReviewSummary()`
 */
#[Fillable(['user_id', 'country_id', 'category_id', 'address_id', 'title', 'description', 'status', 'price', 'currency', 'details', 'published_at', 'featured_until', 'paid_until'])]
#[UseFactory(OfferFactory::class)]
class Offer extends Model implements HasMedia
{
    /** @use HasFactory<OfferFactory> */
    use HasFactory, HasTranslations, InteractsWithMedia, SoftDeletes;

    /**
     * Textes traduits (décision 2026-11-18-offres-multilingues-categories-arbre) :
     * lus dans la langue courante, sinon la langue par défaut, sinon une autre.
     *
     * @var list<string>
     */
    public array $translatable = ['title', 'description'];

    public const PHOTOS = 'photos';

    /**
     * Publier fixe la date de publication, une fois.
     */
    protected static function booted(): void
    {
        static::saving(function (Offer $offer): void {
            if ($offer->status === OfferStatusEnum::PUBLISHED && $offer->published_at === null) {
                $offer->published_at = now();
            }
        });
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => OfferStatusEnum::class,
            'price' => 'decimal:2',
            'details' => 'array',
            'published_at' => 'datetime',
            'featured_until' => 'datetime',
            'paid_until' => 'datetime',
        ];
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection(self::PHOTOS)
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp'])
            ->useDisk('public');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Son pays, l'espace où elle se trouve (décision 2026-11-18-espace-pays).
     *
     * @return BelongsTo<Country, $this>
     */
    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    /**
     * @return BelongsTo<OfferCategory, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(OfferCategory::class);
    }

    /**
     * @return BelongsTo<Address, $this>
     */
    public function address(): BelongsTo
    {
        return $this->belongsTo(Address::class);
    }

    /**
     * Ses avis (décision 2026-11-19-contrat-avis-offres).
     *
     * @return HasMany<OfferReview, $this>
     */
    public function reviews(): HasMany
    {
        return $this->hasMany(OfferReview::class);
    }

    /**
     * Ses favoris (décision 2026-11-19-contrat-vedette-favoris).
     *
     * @return HasMany<OfferFavorite, $this>
     */
    public function favorites(): HasMany
    {
        return $this->hasMany(OfferFavorite::class);
    }

    /**
     * Est-elle en favori de `$userId` ? Chargé en `is_favorite`.
     *
     * @param  Builder<Offer>  $query
     */
    public function scopeWithFavoriteOf(Builder $query, int|string|null $userId): void
    {
        $query->withExists(['favorites as is_favorite' => fn (Builder $favorites) => $favorites->where('user_id', $userId)]);
    }

    /**
     * En vedette maintenant : sa date n'est pas passée.
     *
     * @param  Builder<Offer>  $query
     */
    public function scopeFeatured(Builder $query): void
    {
        $query->where('featured_until', '>', now());
    }

    /**
     * Pas en vedette maintenant.
     *
     * @param  Builder<Offer>  $query
     */
    public function scopeNotFeatured(Builder $query): void
    {
        $query->where(fn (Builder $dates) => $dates->whereNull('featured_until')->orWhere('featured_until', '<=', now()));
    }

    /**
     * Point d'API de son cœur de favori, relatif à l'API (« /v1/offers/12/favorite »).
     */
    public function favoriteEndpoint(): string
    {
        return '/'.Str::after(ltrim(route('api.v1.offers.favorite.save', ['offer' => $this->getKey()], false), '/'), 'api/');
    }

    public function isFeatured(): bool
    {
        return $this->featured_until !== null && $this->featured_until->isFuture();
    }

    /**
     * Moyenne et nombre d'avis, calculés dans la requête.
     *
     * @param  Builder<Offer>  $query
     */
    public function scopeWithReviewSummary(Builder $query): void
    {
        $query->withAvg('reviews', 'rating')->withCount('reviews');
    }

    /**
     * Ses avis s'affichent-ils ? Seulement tant qu'elle est publiée.
     */
    public function showsReviews(): bool
    {
        return $this->status === OfferStatusEnum::PUBLISHED;
    }

    /**
     * URL de sa première photo, ou `null` (décision 2026-11-18-liste-photo-barre-connecte).
     */
    public function photoUrl(): ?string
    {
        return $this->getFirstMediaUrl(self::PHOTOS) ?: null;
    }

    /**
     * Valeur brute d'un attribut du domaine (par langue s'il est traduisible).
     */
    public function detail(string $name): mixed
    {
        return ($this->details ?? [])[$name] ?? null;
    }

    /**
     * Valeur d'un attribut du domaine dans une langue : la sienne, sinon la langue
     * par défaut, sinon une autre. Un attribut non traduit est rendu tel quel.
     */
    public function detailIn(string $name, string $locale): mixed
    {
        $value = $this->detail($name);

        if (! is_array($value)) {
            return $value;
        }

        return $value[$locale] ?? $value[Locales::default()] ?? (reset($value) ?: null);
    }
}
