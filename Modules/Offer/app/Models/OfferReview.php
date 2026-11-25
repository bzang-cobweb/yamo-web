<?php

namespace Modules\Offer\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Auth\Models\User;
use Modules\Offer\Database\Factories\OfferReviewFactory;

/**
 * L'avis d'un membre sur une offre (décision 2026-11-19-contrat-avis-offres) :
 * une note de 1 à 5 et un commentaire, publié aussitôt. Un seul par membre et par
 * offre. Le commentaire n'est pas traduit.
 *
 * @property int $offer_id
 * @property int $user_id
 * @property int $rating
 * @property string $comment
 * @property-read Offer $offer
 * @property-read User $user
 */
#[Fillable(['offer_id', 'user_id', 'rating', 'comment'])]
#[UseFactory(OfferReviewFactory::class)]
class OfferReview extends Model
{
    /** @use HasFactory<OfferReviewFactory> */
    use HasFactory;

    public const MIN_RATING = 1;

    public const MAX_RATING = 5;

    public const COMMENT_MAX_LENGTH = 1000;

    /**
     * @return array<string, string>
     */
    /** Chaque avis donné, changé ou retiré met à jour la note de son offre (décision 2026-11-25-accueil-a-grande-echelle). */
    protected static function booted(): void
    {
        static::saved(fn (self $review) => $review->offer?->refreshReviewSummary());
        static::deleted(fn (self $review) => $review->offer?->refreshReviewSummary());
    }

    protected function casts(): array
    {
        return ['rating' => 'integer'];
    }

    /**
     * @return BelongsTo<Offer, $this>
     */
    public function offer(): BelongsTo
    {
        return $this->belongsTo(Offer::class)->withTrashed();
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
