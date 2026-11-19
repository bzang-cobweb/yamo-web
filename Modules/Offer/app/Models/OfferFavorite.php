<?php

namespace Modules\Offer\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Auth\Models\User;

/**
 * Une offre gardée en favori par un membre (décision 2026-11-19-contrat-vedette-favoris).
 * Un seul favori par membre et par offre ; il survit à l'archivage de l'offre.
 *
 * @property int $user_id
 * @property int $offer_id
 * @property-read Offer $offer
 * @property-read User $user
 */
#[Fillable(['user_id', 'offer_id'])]
class OfferFavorite extends Model
{
    /**
     * @return BelongsTo<Offer, $this>
     */
    public function offer(): BelongsTo
    {
        return $this->belongsTo(Offer::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
