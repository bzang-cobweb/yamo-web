<?php

namespace Modules\Offer\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Une offre rangée dans une liste de favoris (décision 2026-11-27-listes-de-favoris) : une fois par liste. Dans
 * « Récemment consultés », `updated_at` est la dernière ouverture de la fiche.
 *
 * @property int $wishlist_id
 * @property int $offer_id
 * @property-read Wishlist $wishlist
 * @property-read Offer $offer
 */
#[Fillable(['wishlist_id', 'offer_id'])]
class WishlistItem extends Model
{
    /**
     * @return BelongsTo<Wishlist, $this>
     */
    public function wishlist(): BelongsTo
    {
        return $this->belongsTo(Wishlist::class);
    }

    /**
     * @return BelongsTo<Offer, $this>
     */
    public function offer(): BelongsTo
    {
        return $this->belongsTo(Offer::class);
    }
}
