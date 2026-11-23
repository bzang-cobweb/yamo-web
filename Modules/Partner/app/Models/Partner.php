<?php

namespace Modules\Partner\Models;

use App\Models\Setting;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Carbon;
use Modules\Auth\Models\User;
use Modules\Location\Models\Country;
use Modules\Location\Support\CurrentCountry;
use Modules\Partner\Database\Factories\PartnerFactory;
use Modules\Partner\Enums\PartnerStatusEnum;
use Modules\Partner\Events\PartnerStatusChanged;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\Translatable\HasTranslations;

/**
 * Un partenaire de l'accueil (décision 2026-11-23-module-partenaires) : une entreprise qui paie pour un
 * espace dans l'app, dans un pays, le temps de sa campagne.
 *
 * @property int $id
 * @property int $country_id
 * @property string $name
 * @property PartnerStatusEnum $status
 * @property Carbon|null $starts_at
 * @property Carbon|null $expires_at
 * @property int $position
 * @property string|null $contact_email
 * @property string|null $contact_phone
 * @property int $views_count
 * @property int $clicks_count
 * @property-read Country $country
 * @property-read Collection<int, User> $users les comptes qui l'administrent
 */
#[Fillable(['country_id', 'name', 'slogan', 'description', 'url', 'cta_label', 'status', 'starts_at', 'expires_at', 'position', 'contact_email', 'contact_phone'])]
#[UseFactory(PartnerFactory::class)]
class Partner extends Model implements HasMedia
{
    /** @use HasFactory<PartnerFactory> */
    use HasFactory, HasTranslations, InteractsWithMedia;

    /** Le logo, une seule image. */
    public const LOGO = 'logo';

    /** @var list<string> */
    public array $translatable = ['name', 'slogan', 'description', 'url', 'cta_label'];

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
            'starts_at' => 'datetime',
            'expires_at' => 'datetime',
            'position' => 'integer',
            'views_count' => 'integer',
            'clicks_count' => 'integer',
        ];
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection(self::LOGO)->singleFile()->useDisk('public');
    }

    /** @return BelongsTo<Country, $this> */
    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
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

    /**
     * Ceux qu'administre le compte `$userId`, dans le pays courant.
     *
     * @param  Builder<Partner>  $query
     */
    public function scopeAdministeredBy(Builder $query, int|string|null $userId): void
    {
        $query->where('country_id', CurrentCountry::require()->getKey())
            ->whereHas('users', fn (Builder $users) => $users->whereKey($userId));
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
        if ($this->starts_at?->isFuture()) {
            return false;
        }
        if (! $paymentRequired) {
            return true;
        }

        return (bool) $this->expires_at?->isFuture();
    }

    /**
     * La même règle que `scopeVisible()`, pour un partenaire chargé (décision 2026-11-24-partenaires-policy-routes).
     */
    public function isVisible(): bool
    {
        if ((int) $this->country_id !== (int) CurrentCountry::require()->getKey()) {
            return false;
        }

        return $this->isShown(Setting::partnerPaymentRequired());
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
            ->where('country_id', CurrentCountry::require()->getKey())
            ->where(fn (Builder $start) => $start->whereNull('starts_at')->orWhere('starts_at', '<=', now()))
            ->when(Setting::partnerPaymentRequired(), fn (Builder $paid) => $paid->where('expires_at', '>', now()));
    }

    public function logoUrl(): ?string
    {
        $url = $this->getFirstMediaUrl(self::LOGO);

        return $url === '' ? null : $url;
    }
}
