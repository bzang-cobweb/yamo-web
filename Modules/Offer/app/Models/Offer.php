<?php

namespace Modules\Offer\Models;

use App\Models\Setting;
use App\Support\Locales;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Modules\Auth\Models\User;
use Modules\Location\Models\Address;
use Modules\Location\Models\Country;
use Modules\Location\Support\CurrentCountry;
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
 * @property string|null $locale langue d'origine (décision 2026-11-19-contrat-langue-saisie-allemand)
 * @property int|null $category_id
 * @property string $title
 * @property string|null $description
 * @property OfferStatusEnum $status
 * @property string|null $price
 * @property string|null $currency
 * @property array<string, mixed>|null $details
 * @property Carbon|null $published_at
 * @property Carbon|null $featured_until en vedette jusqu'à cette date (décision 2026-11-19-contrat-vedette-favoris)
 * @property Carbon|null $expires_at date d'expiration, effective si le paiement est appliqué (décision 2026-11-19-contrat-paiement-visibilite-avis)
 * @property bool|null $is_favorite chargé par `withFavoriteOf()`
 * @property-read User $user
 * @property-read Country|null $country
 * @property-read OfferCategory|null $category
 * @property-read OfferAddress|null $offerAddress l'adresse et son indication (décision 2026-11-19-contrat-adresse-offre-table)
 * @property-read Address|null $address l'adresse partagée, à travers `offerAddress`
 * @property-read Collection<int, OfferInformation> $informations
 * @property float|string|null $reviews_avg_rating moyenne des notes, chargée par `withReviewSummary()`
 * @property int|null $reviews_count nombre d'avis, chargé par `withReviewSummary()`
 */
#[Fillable(['user_id', 'country_id', 'locale', 'category_id', 'title', 'description', 'status', 'price', 'currency', 'details', 'published_at', 'featured_until', 'expires_at'])]
#[UseFactory(OfferFactory::class)]
class Offer extends Model implements HasMedia
{
    /** @use HasFactory<OfferFactory> */
    use HasFactory, InteractsWithMedia, SoftDeletes;

    use HasTranslations { normalizeLocale as translatableLocale; }

    /**
     * Textes traduits (décision 2026-11-18-offres-multilingues-categories-arbre) :
     * lus dans la langue courante, sinon la langue par défaut, sinon une autre.
     *
     * @var list<string>
     */
    public array $translatable = ['title', 'description'];

    public const PHOTOS = 'photos';

    /**
     * Un texte manquant dans `$locale` s'affiche dans la langue d'origine de l'offre,
     * puis la langue par défaut, puis une autre (décision 2026-11-19-contrat-langue-saisie-allemand).
     */
    protected function normalizeLocale(string $key, string $locale, bool $useFallbackLocale): string
    {
        return OriginalLocale::pick($this->getTranslatedLocales($key), $locale, $useFallbackLocale ? $this->locale : null)
            ?? $this->translatableLocale($key, $locale, $useFallbackLocale);
    }

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
            'expires_at' => 'datetime',
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
     * Son adresse et l'indication propre à l'offre (décision 2026-11-19-contrat-adresse-offre-table).
     *
     * @return HasOne<OfferAddress, $this>
     */
    public function offerAddress(): HasOne
    {
        return $this->hasOne(OfferAddress::class);
    }

    /**
     * L'adresse partagée du pays, à travers `offerAddress`.
     *
     * @return HasOneThrough<Address, OfferAddress, $this>
     */
    public function address(): HasOneThrough
    {
        return $this->hasOneThrough(Address::class, OfferAddress::class, 'offer_id', 'id', 'id', 'address_id');
    }

    /**
     * Les demandes de réservation ou de visite (décision 2026-11-19-contrat-reservations).
     *
     * @return HasMany<Booking, $this>
     */
    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    /**
     * « Rue du Lac 4, 1200 Genève, Suisse », le pays toujours, dans la langue courante
     * (décision 2026-11-19-fiche-localisation-accordeon) ; sans adresse, `null`. Sert la
     * fiche et le calendrier.
     */
    public function fullAddress(): ?string
    {
        $address = $this->address;

        if ($address === null) {
            return null;
        }

        return implode(', ', array_filter([trim((string) $address->street), $address->subtitle()], fn (string $part): bool => $part !== '')) ?: null;
    }

    /**
     * Les indications pour trouver l'adresse (« Portail vert, 2e étage »), dans la
     * langue `$locale` ; sans indication, `null`. Sert la fiche et le calendrier.
     */
    public function indication(string $locale): ?string
    {
        $text = trim((string) $this->offerAddress?->getTranslation('indication', $locale));

        return $text === '' ? null : $text;
    }

    /**
     * Les jours bloqués par le propriétaire (décision 2026-11-19-contrat-calendrier-offre).
     *
     * @return HasMany<OfferBlockedDate, $this>
     */
    public function blockedDates(): HasMany
    {
        return $this->hasMany(OfferBlockedDate::class);
    }

    /**
     * Ses blocs d'informations, dans l'ordre (décision 2026-11-19-contrat-offer-informations).
     *
     * @return HasMany<OfferInformation, $this>
     */
    public function informations(): HasMany
    {
        return $this->hasMany(OfferInformation::class)->orderBy('position')->orderBy('id');
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
     * Les offres que voient les autres que leur auteur (décision
     * 2026-11-19-contrat-paiement-visibilite-avis) : publiées, du pays courant et,
     * si le paiement est appliqué, pas expirées (sans date : non visibles). La fiche,
     * l'accueil, les favoris et les avis suivent cette règle ; voir `isVisibleToOthers()`.
     *
     * @param  Builder<Offer>  $query
     */
    public function scopeVisibleToOthers(Builder $query): void
    {
        $query->where($query->qualifyColumn('status'), OfferStatusEnum::PUBLISHED)
            ->where($query->qualifyColumn('country_id'), CurrentCountry::require()->getKey())
            ->when(Setting::paymentRequired(), fn (Builder $query) => $query->where($query->qualifyColumn('expires_at'), '>', now()));
    }

    /**
     * La même règle que `scopeVisibleToOthers()`, pour une offre chargée.
     */
    public function isVisibleToOthers(): bool
    {
        return $this->status === OfferStatusEnum::PUBLISHED
            && (int) $this->country_id === (int) CurrentCountry::require()->getKey()
            && (! Setting::paymentRequired() || ($this->expires_at !== null && $this->expires_at->isFuture()));
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
     * d'origine de l'offre, la langue par défaut, une autre. Un attribut non traduit est rendu tel quel, une
     * liste aussi (les équipements choisis) : seules les traductions sont rangées
     * par langue.
     */
    public function detailIn(string $name, string $locale): mixed
    {
        $value = $this->detail($name);

        if (! is_array($value) || array_is_list($value)) {
            return $value;
        }

        // La langue demandée, puis d'origine, puis par défaut, puis une autre.
        return $value[$locale] ?? ($this->locale !== null ? ($value[$this->locale] ?? null) : null) ?? $value[Locales::default()] ?? (reset($value) ?: null);
    }
}
