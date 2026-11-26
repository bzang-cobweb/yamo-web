<?php

namespace Modules\Partner\Models;

use App\Models\Concerns\HasCountry;
use App\Models\Concerns\HasPaymentRange;
use App\Models\Setting;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Carbon;
use Modules\Auth\Models\User;
use Modules\Notification\Concerns\HasNotifiableGroup;
use Modules\Notification\Contracts\NotifiableGroup;
use Modules\Partner\Database\Factories\PartnerFactory;
use Modules\Partner\Enums\PartnerStatusEnum;
use Modules\Partner\Events\PartnerStatusChanged;
use Modules\Partner\Notifications\PartnerNotificationFilter;
use Modules\Partner\Services\PartnerLogoService;
use Spatie\Image\Enums\Fit;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\Sluggable\HasTranslatableSlug;
use Spatie\Sluggable\SlugOptions;
use Spatie\Translatable\HasTranslations;

/**
 * Un partenaire de l'accueil (décision 2026-11-23-module-partenaires) : une entreprise qui paie pour un
 * espace dans l'app, dans un pays, le temps de sa campagne.
 *
 * @property int $id
 * @property string $name
 * @property PartnerStatusEnum $status
 * @property int $position
 * @property string|null $contact_email
 * @property string|null $contact_phone
 * @property int $views_count
 * @property int $clicks_count
 * @property Carbon|null $featured_until en vedette jusqu'à cette date (décision 2026-11-25-vedettes-d-abord)
 * @property-read Collection<int, User> $users les comptes qui l'administrent
 */
#[Fillable(['country_id', 'name', 'slogan', 'description', 'website', 'cta_label', 'status', 'starts_at', 'expires_at', 'featured_until', 'position', 'contact_email', 'contact_phone'])]
#[UseFactory(PartnerFactory::class)]
class Partner extends Model implements HasMedia, NotifiableGroup
{
    /** @use HasFactory<PartnerFactory> */
    use HasCountry, HasFactory, HasNotifiableGroup, HasPaymentRange, HasTranslatableSlug, HasTranslations, InteractsWithMedia;

    /** Le logo, une seule image. */
    public const LOGO = 'logo';

    /**
     * Le nom et le slogan, saisis dans l'app comme au back-office : 50 caractères au plus (décision
     * 2026-11-24-partenaire-nom-slogan-50) ; la base en garde davantage.
     */
    public const NAME_MAX_LENGTH = 50;

    public const SLOGAN_MAX_LENGTH = 50;

    /** @var list<string> */
    public array $translatable = ['name', 'url', 'slogan', 'description', 'website', 'cta_label'];

    /**
     * Le slug `url`, tiré du nom dans chaque langue (décision 2026-11-25-slug-url-offres-partenaires), unique par
     * pays et par langue (décision 2026-11-25-slug-unique-par-pays).
     */
    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()->generateSlugsFrom('name')->saveSlugsTo('url')
            ->extraScope(fn (Builder $query): Builder => $query->where('country_id', $this->country_id));
    }

    /** Un nouveau partenaire se range à la fin (décision 2026-11-23-partenaires-triables) ; un changement de statut est annoncé. */
    protected static function booted(): void
    {
        static::creating(function (Partner $partner): void {
            if (! $partner->isDirty('position')) {
                $partner->position = (int) self::query()->max('position') + 1;
            }
        });

        // Un changement de statut, d'où qu'il vienne, prévient ses comptes (décision 2026-11-24-partenaires-notifications) ;
        // celui qui le change n'est pas prévenu.
        static::updated(function (Partner $partner): void {
            if ($partner->wasChanged('status')) {
                $previous = $partner->getOriginal('status');
                PartnerStatusChanged::dispatch($partner, $previous instanceof PartnerStatusEnum ? $previous : PartnerStatusEnum::from((string) $previous), request()->user()?->getKey());
            }
        });
    }

    protected function casts(): array
    {
        return [
            'status' => PartnerStatusEnum::class,
            'position' => 'integer',
            'views_count' => 'integer',
            'clicks_count' => 'integer',
            'featured_until' => 'date',
        ];
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection(self::LOGO)->singleFile()->useDisk('public');
    }

    /**
     * Les comptes qui lui sont rattachés : plusieurs, ajoutés au back-office ; `pivot.is_admin` : administrateur,
     * vrai pour celui qui le propose (décision 2026-11-24-partenaires-par-les-membres). Tous le modifient ; seul un
     * administrateur le retire.
     *
     * @return BelongsToMany<User, $this>
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)->withPivot('is_admin')->withTimestamps();
    }

    /** Le préfixe du fil de ses notifications, partagé par tous ses comptes (décision 2026-11-24-notifications-fil-de-groupe). */
    public static function groupThreadPrefix(): string
    {
        // Préfixé par la clé du type « Partenaire » (décision 2026-11-26-prefixes-de-fil-des-filtres).
        return PartnerNotificationFilter::partnerThreadPrefix();
    }

    /**
     * Les partenaires auxquels le compte est rattaché : il lit tout leur fil de notifications.
     *
     * @return list<int|string>
     */
    protected static function groupKeysOf(User $user): array
    {
        return array_values(self::query()->whereHas('users', fn (Builder $users) => $users->whereKey($user->getKey()))->pluck('id')->all());
    }

    /**
     * Ceux qu'administre le compte `$userId`, dans le pays courant.
     *
     * @param  Builder<Partner>  $query
     */
    public function scopeAdministeredBy(Builder $query, int|string|null $userId): void
    {
        $query->visibleCountry()->whereHas('users', fn (Builder $users) => $users->whereKey($userId));
    }

    /**
     * Le compte `$userId` en est-il un administrateur (`is_admin`) ?
     */
    public function hasAdmin(int|string|null $userId): bool
    {
        if ($userId === null) {
            return false;
        }

        return $this->users()->whereKey($userId)->wherePivot('is_admin', true)->exists();
    }

    /**
     * Le compte `$userId` lui est-il rattaché ?
     */
    public function isAdministeredBy(int|string|null $userId): bool
    {
        if ($userId === null) {
            return false;
        }

        return $this->users()->whereKey($userId)->exists();
    }

    /**
     * Affiché dans l'app maintenant, la même règle que `scopeVisible()` hors pays : publié, campagne commencée,
     * et, le paiement des partenaires appliqué, pas expiré.
     */
    public function isShown(bool $paymentRequired): bool
    {
        if ($this->status !== PartnerStatusEnum::PUBLISHED) {
            return false;
        }
        if (! $this->isPaymentStarted()) {
            return false;
        }

        return $this->isInPaymentRange($paymentRequired);
    }

    /**
     * Sa pastille pour ses comptes : son statut, et publié, s'il est affiché maintenant ou pas (campagne, paiement)
     * (décisions 2026-11-24-partenaires-statuts et 2026-11-24-partenaire-page-statut) ; « Mes partenaires » et sa page.
     * `$paymentRequired` : le réglage déjà lu (une liste le lit une fois pour toutes ses lignes).
     *
     * @return array{label: string, tone: string}
     */
    public function statusTag(bool $paymentRequired): array
    {
        if ($this->status !== PartnerStatusEnum::PUBLISHED) {
            return ['label' => $this->status->label(), 'tone' => $this->status->tone()];
        }
        if ($this->isShown($paymentRequired)) {
            return ['label' => __('partner::partner.member.status.shown'), 'tone' => 'success'];
        }

        return ['label' => __('partner::partner.member.status.hidden'), 'tone' => 'neutral'];
    }

    /**
     * La même règle que `scopeVisible()`, pour un partenaire chargé (décision 2026-11-24-partenaires-policy-routes).
     */
    public function isVisible(): bool
    {
        if (! $this->isInCountry()) {
            return false;
        }

        return $this->isShown(static::paymentRequired());
    }

    /** Le paiement des partenaires est-il appliqué (décision 2026-11-23-paiement-partenaires) ? */
    public static function paymentRequired(): bool
    {
        return Setting::partnerPaymentRequired();
    }

    /**
     * Visibles dans l'app : publiés (décision 2026-11-24-partenaires-statuts), du pays courant, campagne commencée ; le paiement des partenaires appliqué,
     * jusqu'à leur date d'expiration, sans date jamais (décision 2026-11-23-paiement-partenaires), comme les offres.
     *
     * @param  Builder<Partner>  $query
     */
    public function scopeVisible(Builder $query): void
    {
        $query->where('status', PartnerStatusEnum::PUBLISHED)
            ->visibleCountry()
            ->paymentStarted()
            ->visiblePaymentRange();
    }

    /**
     * En vedette : jusqu'à son jour inclus (décisions 2026-11-25-vedettes-d-abord et 2026-11-25-vedette-en-dates), comme
     * une offre.
     *
     * @param  Builder<Partner>  $query
     */
    public function scopeFeatured(Builder $query): void
    {
        $query->whereDate($query->qualifyColumn('featured_until'), '>=', today());
    }

    /**
     * La ligne sous le nom d'une carte : le slogan, sinon la description ; `null` sans l'un ni l'autre (décision
     * 2026-11-24-mes-partenaires-slogan), sur « Mes partenaires » comme sur la page « Partenaires ».
     */
    public function cardLine(string $locale): ?string
    {
        $slogan = (string) $this->getTranslation('slogan', $locale);
        if ($slogan !== '') {
            return $slogan;
        }

        $description = (string) $this->getTranslation('description', $locale);
        if ($description === '') {
            return null;
        }

        return $description;
    }

    /**
     * Une conversion par endroit où le logo s'affiche (`PartnerLogoService::conversions()`, décision
     * 2026-11-24-partenaire-logo-conversions), produite tout de suite : aucune file d'attente ne tourne.
     */
    public function registerMediaConversions(?Media $media = null): void
    {
        foreach (PartnerLogoService::conversions() as $name => $size) {
            $this->addMediaConversion((string) $name)
                ->performOnCollections(self::LOGO)
                ->fit(Fit::Max, (int) $size, (int) $size)
                ->format('webp')
                ->quality(PartnerLogoService::quality())
                ->nonOptimized()
                ->nonQueued();
        }
    }

    /**
     * L'adresse du logo ; avec `$conversion` (`home`, `list`, `detail`), celle de sa conversion, ou celle du
     * logo tant que la conversion n'est pas produite ; `null` sans logo.
     */
    public function logoUrl(?string $conversion = null): ?string
    {
        $media = $this->getFirstMedia(self::LOGO);
        if ($media === null) {
            return null;
        }
        if ($conversion === null) {
            return $media->getUrl();
        }
        if (! $media->hasGeneratedConversion($conversion)) {
            return $media->getUrl();
        }

        return $media->getUrl($conversion);
    }
}
