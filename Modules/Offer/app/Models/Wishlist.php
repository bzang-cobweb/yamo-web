<?php

namespace Modules\Offer\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Auth\Models\User;
use Modules\Offer\Enums\WishlistKindEnum;

/**
 * Une liste de favoris d'un membre (décision 2026-11-27-listes-de-favoris) : un nom et ses offres. Sa liste
 * « Récemment consultés » (`kind` = `recent`) n'a pas de nom saisi : son nom est traduit à l'affichage (`displayName()`).
 *
 * @property int $user_id
 * @property string|null $name
 * @property WishlistKindEnum $kind
 * @property-read User $user
 */
#[Fillable(['user_id', 'name', 'kind'])]
class Wishlist extends Model
{
    /** La longueur maximale d'un nom. */
    public const NAME_MAX_LENGTH = 60;

    /** Le nombre d'offres gardées dans « Récemment consultés » : au-delà, la plus ancienne sort. */
    public const RECENT_LIMIT = 50;

    protected function casts(): array
    {
        return ['kind' => WishlistKindEnum::class];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return HasMany<WishlistItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(WishlistItem::class);
    }

    public function isRecent(): bool
    {
        return $this->kind === WishlistKindEnum::RECENT;
    }

    /** Son nom à l'affichage : celui saisi, ou « Récemment consultés ». */
    public function displayName(?string $locale = null): string
    {
        return $this->isRecent() ? (string) __('offer::offer.wishlists.recent', [], $locale) : (string) $this->name;
    }

    /**
     * Les listes nommées par le membre, sans « Récemment consultés ».
     *
     * @param  Builder<Wishlist>  $query
     */
    public function scopeCustom(Builder $query): void
    {
        $query->where('kind', WishlistKindEnum::CUSTOM->value);
    }

    /**
     * Les listes de `$userId`.
     *
     * @param  Builder<Wishlist>  $query
     */
    public function scopeOwnedBy(Builder $query, int|string|null $userId): void
    {
        $query->where('user_id', $userId);
    }
}
