<?php

namespace Modules\Offer\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Un jour bloqué par le propriétaire de l'offre (décision 2026-11-19-contrat-calendrier-offre).
 *
 * @property int $id
 * @property int $offer_id
 * @property Carbon $date
 */
#[Fillable(['offer_id', 'date'])]
class OfferBlockedDate extends Model
{
    protected function casts(): array
    {
        return ['date' => 'date'];
    }

    /**
     * @return BelongsTo<Offer, $this>
     */
    public function offer(): BelongsTo
    {
        return $this->belongsTo(Offer::class);
    }
}
