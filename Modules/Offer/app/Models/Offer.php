<?php

namespace Modules\Offer\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Modules\Auth\Models\User;
use Modules\Location\Models\Address;
use Modules\Offer\Database\Factories\OfferFactory;
use Modules\Offer\Enums\OfferStatusEnum;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

/**
 * Une offre (décision 2026-11-18-offres-et-domaines) : ce qu'un compte propose,
 * quel que soit le domaine du projet. Les champs communs sont des colonnes ; les
 * attributs propres au domaine (`Domain::attributes()`) sont dans `details`.
 *
 * @property int $user_id
 * @property int|null $category_id
 * @property int|null $address_id
 * @property string $title
 * @property string|null $description
 * @property OfferStatusEnum $status
 * @property string|null $price
 * @property string|null $currency
 * @property array<string, mixed>|null $details
 * @property Carbon|null $published_at
 * @property-read User $user
 * @property-read OfferCategory|null $category
 * @property-read Address|null $address
 */
#[Fillable(['user_id', 'category_id', 'address_id', 'title', 'description', 'status', 'price', 'currency', 'details', 'published_at'])]
#[UseFactory(OfferFactory::class)]
class Offer extends Model implements HasMedia
{
    /** @use HasFactory<OfferFactory> */
    use HasFactory, InteractsWithMedia, SoftDeletes;

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
     * Valeur d'un attribut du domaine.
     */
    public function detail(string $name): mixed
    {
        return ($this->details ?? [])[$name] ?? null;
    }
}
