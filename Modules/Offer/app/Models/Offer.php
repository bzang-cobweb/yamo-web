<?php

namespace Modules\Offer\Models;

use App\Models\Concerns\HasCountry;
use App\Models\Concerns\HasPaymentRange;
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
use Illuminate\Support\Number;
use Illuminate\Support\Str;
use Modules\Auth\Models\User;
use Modules\Location\Models\Address;
use Modules\Offer\Database\Factories\OfferFactory;
use Modules\Offer\Enums\OfferStatusEnum;
use Modules\Offer\Services\OfferPhotoService;
use Spatie\Image\Enums\Fit;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\Sluggable\HasTranslatableSlug;
use Spatie\Sluggable\SlugOptions;
use Spatie\Translatable\HasTranslations;

/**
 * Une offre (décision 2026-11-18-offres-et-domaines) : ce qu'un compte propose,
 * quel que soit le domaine du projet. Les champs communs sont des colonnes ; les
 * attributs propres au domaine (`Domain::attributes()`) sont dans `details` ; un
 * attribut traduisible y est rangé par langue (`{"note": {"fr": …, "en": …}}`).
 *
 * @property int $user_id
 * @property string|null $locale langue d'origine (décision 2026-11-19-contrat-langue-saisie-allemand)
 * @property int|null $category_id
 * @property string $title
 * @property string|null $description
 * @property OfferStatusEnum $status
 * @property string|null $price
 * @property string|null $currency
 * @property array<string, mixed>|null $details
 * @property bool $show_address afficher l'adresse sur la fiche (décision 2026-11-21-afficher-adresse)
 * @property string|null $phone téléphone de contact, E.164 (décision 2026-11-21-contact-offre)
 * @property string|null $email e-mail de contact (décision 2026-11-21-contact-offre)
 * @property Carbon|null $published_at
 * @property Carbon|null $featured_until en vedette jusqu'à cette date (décision 2026-11-19-contrat-vedette-favoris)
 * @property Carbon|null $boosted_until remontée dans le carrousel de sa région jusqu'à ce jour (décision 2026-11-25-remontee-carrousel)
 * @property bool|null $is_favorite chargé par `withFavoriteOf()`
 * @property-read User $user
 * @property-read OfferCategory|null $category
 * @property-read OfferAddress|null $offerAddress l'adresse et son indication (décision 2026-11-19-contrat-adresse-offre-table)
 * @property-read Address|null $address l'adresse partagée, à travers `offerAddress`
 * @property-read Collection<int, OfferInformation> $informations
 * @property float|string|null $reviews_avg_rating moyenne des notes, enregistrée et tenue à jour par ses avis
 * @property int $reviews_count nombre d'avis, enregistré et tenu à jour par ses avis (décision
 *                              2026-11-25-accueil-a-grande-echelle)
 */
#[Fillable(['user_id', 'country_id', 'locale', 'category_id', 'title', 'description', 'status', 'price', 'currency', 'details', 'show_address', 'phone', 'email', 'published_at', 'featured_until', 'boosted_until', 'starts_at', 'expires_at'])]
#[UseFactory(OfferFactory::class)]
class Offer extends Model implements HasMedia
{
    /** L'adresse affichée par défaut, comme la colonne (décision 2026-11-21-afficher-adresse). */
    protected $attributes = ['show_address' => true];

    /** @use HasFactory<OfferFactory> */
    use HasCountry, HasFactory, HasPaymentRange, HasTranslatableSlug, InteractsWithMedia, SoftDeletes;

    use HasTranslations { normalizeLocale as translatableLocale; }

    /**
     * Textes traduits (décision 2026-11-18-offres-multilingues-categories-arbre) :
     * lus dans la langue courante, sinon la langue par défaut, sinon une autre.
     *
     * @var list<string>
     */
    public array $translatable = ['title', 'url', 'description'];

    public const PHOTOS = 'photos';

    /**
     * Le titre, saisi dans l'app comme au back-office : 50 caractères au plus (décision 2026-11-25-titre-offre-50) ;
     * la base en garde 255.
     */
    public const TITLE_MAX_LENGTH = 50;

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
     * Le slug `url`, tiré du titre dans chaque langue (décision 2026-11-25-slug-url-offres-partenaires), unique par
     * pays et par langue (décision 2026-11-25-slug-unique-par-pays).
     */
    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()->generateSlugsFrom('title')->saveSlugsTo('url')
            ->extraScope(fn (Builder $query): Builder => $query->where('country_id', $this->country_id));
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
            'show_address' => 'boolean',
            'published_at' => 'datetime',
            // Un jour, sans heure (décision 2026-11-25-vedette-en-dates).
            'featured_until' => 'date',
            // Enregistrés et tenus à jour par ses avis (décision 2026-11-25-accueil-a-grande-echelle).
            'reviews_avg_rating' => 'decimal:2',
            'reviews_count' => 'integer',
            // Remontée dans le carrousel de sa région, jour inclus (décision 2026-11-25-remontee-carrousel).
            'boosted_until' => 'date',
        ];
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection(self::PHOTOS)
            ->acceptsMimeTypes(OfferPhotoService::acceptedMimeTypes())
            ->useDisk('public');
    }

    /**
     * Une conversion par endroit où la photo s'affiche (`OfferPhotoService::conversions()`, décision
     * 2026-11-24-avatar-photos-offre-conversions), bornée par la largeur (`OfferPhotoService::bounds()`), produite tout
     * de suite : aucune file d'attente ne tourne.
     */
    public function registerMediaConversions(?Media $media = null): void
    {
        foreach (OfferPhotoService::conversions() as $name => $size) {
            [$width, $height] = OfferPhotoService::bounds((int) $size);
            $this->addMediaConversion((string) $name)
                ->performOnCollections(self::PHOTOS)
                ->fit(Fit::Max, $width, $height)
                ->format('webp')
                ->quality(OfferPhotoService::quality())
                ->nonOptimized()
                ->nonQueued();
        }
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
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
     * En vedette maintenant : jusqu'à son jour inclus (décision 2026-11-25-vedette-en-dates).
     *
     * @param  Builder<Offer>  $query
     */
    public function scopeFeatured(Builder $query): void
    {
        $query->whereDate($query->qualifyColumn('featured_until'), '>=', today());
    }

    /**
     * Remontée en tête du carrousel de sa région maintenant : jusqu'à son jour inclus (décision
     * 2026-11-25-remontee-carrousel).
     *
     * @param  Builder<Offer>  $query
     */
    public function scopeBoosted(Builder $query): void
    {
        $query->whereDate($query->qualifyColumn('boosted_until'), '>=', today());
    }

    /**
     * Pas en vedette maintenant.
     *
     * @param  Builder<Offer>  $query
     */
    public function scopeNotFeatured(Builder $query): void
    {
        $query->where(fn (Builder $dates) => $dates->whereNull('featured_until')->orWhereDate('featured_until', '<', today()));
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
        return $this->featured_until !== null && $this->featured_until->gte(today());
    }

    /**
     * Moyenne et nombre d'avis : ils sont enregistrés sur l'offre (décision 2026-11-25-accueil-a-grande-echelle), rien à
     * calculer ; gardé pour les listes qui le demandent.
     *
     * @param  Builder<Offer>  $query
     */
    public function scopeWithReviewSummary(Builder $query): void {}

    /**
     * « Avis (4,7) » : le titre des avis avec la note moyenne enregistrée, dans la langue ; « Avis » sans avis
     * (décisions 2026-11-25-avis-note-dans-le-titre et 2026-11-25-liste-avis-titre-nombre).
     */
    public function reviewsTitle(?string $locale = null): string
    {
        $locale ??= app()->getLocale();
        $title = (string) __('offer::offer.section_titles.reviews', [], $locale);
        if ($this->reviews_avg_rating === null) {
            return $title;
        }

        return $title.' ('.Number::format((float) $this->reviews_avg_rating, precision: 1, locale: $locale).')';
    }

    /**
     * Recalcule et enregistre sa moyenne et son nombre d'avis, sans toucher à sa date de mise à jour (décision
     * 2026-11-25-accueil-a-grande-echelle) : appelé à chaque avis donné, changé ou retiré.
     */
    public function refreshReviewSummary(): void
    {
        $count = $this->reviews()->count();
        $average = $count === 0 ? null : round((float) $this->reviews()->avg('rating'), 2);

        $summary = ['reviews_avg_rating' => $average, 'reviews_count' => $count];
        static::withTrashed()->whereKey($this->getKey())->toBase()->update($summary);
        $this->forceFill($summary)->syncOriginalAttributes(array_keys($summary));
    }

    /**
     * Les offres que voient les autres que leur auteur (décision
     * 2026-11-19-contrat-paiement-visibilite-avis) : publiées, du pays courant et,
     * si le paiement est appliqué, dans leur période payée (`starts_at`, `expires_at`, décision
     * 2026-11-23-offre-paye-a-partir-du ; sans date de fin : non visibles). La fiche, l'accueil, les favoris et les avis suivent cette règle ; voir `isVisibleToOthers()`.
     *
     * @param  Builder<Offer>  $query
     */
    public function scopeVisibleToOthers(Builder $query): void
    {
        $query->where($query->qualifyColumn('status'), OfferStatusEnum::PUBLISHED)
            ->visibleCountry()
            ->visiblePaymentRange();
    }

    /** Le paiement des propriétaires est-il appliqué (décision 2026-11-19-contrat-paiement-visibilite-avis) ? */
    public static function paymentRequired(): bool
    {
        return Setting::paymentRequired();
    }

    /**
     * A-t-il une demande en cours (en attente, ou acceptée et pas terminée) ? Alors son statut ne change pas et il ne
     * se retire pas (décision 2026-11-22-logement-verrouille-par-demandes).
     */
    public function hasOpenBookings(): bool
    {
        // Déjà chargé par la liste (`withExists`, « Mes logements ») : sans requête de plus.
        if (array_key_exists('has_open_bookings', $this->attributes)) {
            return (bool) $this->attributes['has_open_bookings'];
        }

        return $this->bookings()->open()->exists();
    }

    /**
     * La même règle que `scopeVisibleToOthers()`, pour une offre chargée.
     */
    public function isVisibleToOthers(): bool
    {
        if ($this->status !== OfferStatusEnum::PUBLISHED) {
            return false;
        }
        if (! $this->isInCountry()) {
            return false;
        }

        return $this->isInPaymentRange();
    }

    /**
     * Ses avis s'affichent-ils ? Seulement tant qu'elle est publiée.
     */
    public function showsReviews(): bool
    {
        return $this->status === OfferStatusEnum::PUBLISHED;
    }

    /**
     * L'adresse de sa première photo (décision 2026-11-18-liste-photo-barre-connecte), ou `null` ; avec `$conversion`
     * (`web`, `detail`, `list`, `home`, `form`), celle de sa conversion (décision 2026-11-24-avatar-photos-offre-conversions).
     */
    public function photoUrl(?string $conversion = null): ?string
    {
        $media = $this->getFirstMedia(self::PHOTOS);
        if ($media === null) {
            return null;
        }

        return self::mediaUrl($media, $conversion);
    }

    /**
     * Les adresses de ses photos, dans l'ordre, à la conversion `$conversion`.
     *
     * @return list<string>
     */
    public function photoUrls(?string $conversion = null): array
    {
        return $this->getMedia(self::PHOTOS)->map(fn (Media $media): string => self::mediaUrl($media, $conversion))->values()->all();
    }

    /**
     * L'adresse d'une photo : celle de sa conversion `$conversion`, ou celle de la photo tant que la conversion n'est
     * pas produite (photo enregistrée avant elle).
     */
    public static function mediaUrl(Media $media, ?string $conversion): string
    {
        if ($conversion === null) {
            return $media->getUrl();
        }
        if (! $media->hasGeneratedConversion($conversion)) {
            return $media->getUrl();
        }

        return $media->getUrl($conversion);
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
