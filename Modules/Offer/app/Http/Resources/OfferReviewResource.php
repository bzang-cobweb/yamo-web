<?php

namespace Modules\Offer\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Offer\Models\OfferReview;

/**
 * Un avis tel que l'app l'affiche (décision 2026-11-19-contrat-avis-offres) : le
 * prénom de l'auteur, la note, le commentaire, la date. Même forme sur la fiche
 * (5 derniers) et dans la liste complète.
 *
 * @property OfferReview $resource
 */
class OfferReviewResource extends JsonResource
{
    /**
     * @return array{id: string, author: string, rating: int, comment: string, created_at: string|null}
     */
    public function toArray(Request $request): array
    {
        $review = $this->resource;

        return [
            'id' => (string) $review->getKey(),
            /** Le prénom de l'auteur seulement. */
            'author' => (string) ($review->user->first_name ?: $review->user->name),
            'rating' => $review->rating,
            'comment' => $review->comment,
            'created_at' => $review->created_at?->toIso8601String(),
        ];
    }
}
