<?php

namespace Modules\Partner\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Modules\Location\Models\Country;
use Modules\Location\Support\CurrentCountry;
use Modules\Partner\Database\Factories\PartnerFactory;
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
 * @property bool $published
 * @property Carbon|null $starts_at
 * @property Carbon|null $expires_at
 * @property int $position
 * @property string|null $contact_email
 * @property string|null $contact_phone
 * @property int $views_count
 * @property int $clicks_count
 * @property-read Country $country
 */
#[Fillable(['country_id', 'name', 'description', 'url', 'cta_label', 'published', 'starts_at', 'expires_at', 'position', 'contact_email', 'contact_phone'])]
#[UseFactory(PartnerFactory::class)]
class Partner extends Model implements HasMedia
{
    /** @use HasFactory<PartnerFactory> */
    use HasFactory, HasTranslations, InteractsWithMedia;

    /** Le logo, une seule image. */
    public const LOGO = 'logo';

    /** @var list<string> */
    public array $translatable = ['name', 'description', 'url', 'cta_label'];

    /** Un nouveau partenaire se range à la fin (décision 2026-11-23-partenaires-triables). */
    protected static function booted(): void
    {
        static::creating(function (Partner $partner): void {
            if (! $partner->isDirty('position')) {
                $partner->position = (int) self::query()->max('position') + 1;
            }
        });
    }

    protected function casts(): array
    {
        return [
            'published' => 'boolean',
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
     * Visibles dans l'app : publiés, du pays courant, campagne commencée et pas finie.
     *
     * @param  Builder<Partner>  $query
     */
    public function scopeVisible(Builder $query): void
    {
        $query->where('published', true)
            ->where('country_id', CurrentCountry::require()->getKey())
            ->where(fn (Builder $start) => $start->whereNull('starts_at')->orWhere('starts_at', '<=', now()))
            ->where(fn (Builder $end) => $end->whereNull('expires_at')->orWhere('expires_at', '>', now()));
    }

    public function logoUrl(): ?string
    {
        $url = $this->getFirstMediaUrl(self::LOGO);

        return $url === '' ? null : $url;
    }
}
