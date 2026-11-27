<?php

namespace Modules\Offer\Http\Resources;

use Bza\Form\Support\DisplayTime;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Offer\Models\OfferReview;

/**
 * Un avis tel que l'app l'affiche (décision 2026-11-19-contrat-avis-offres) : le
 * prénom de l'auteur (et son lieu, s'il l'autorise), la note, le commentaire, la date. Même forme sur la fiche
 * (5 derniers) et dans la liste complète.
 *
 * @property OfferReview $resource
 */
class OfferReviewResource extends JsonResource
{
    /**
     * @return array{id: string, author: string, author_location: string|null, rating: float, comment: string, created_at: string|null, date: string|null}
     */
    public function toArray(Request $request): array
    {
        $review = $this->resource;

        return [
            'id' => (string) $review->getKey(),
            /** Le prénom de l'auteur seulement. */
            'author' => (string) ($review->user->first_name ?: $review->user->name),
            /** Son pays, sa région et sa ville (« Douala, Littoral, Cameroun »), s'il l'autorise ; `null` sinon (décision 2026-11-27-confidentialite). */
            'author_location' => $review->user->reviewLocation(),
            'rating' => $review->rating,
            'comment' => $review->comment,
            'created_at' => $review->created_at?->toIso8601String(),
            /** La date, formatée (« 18 nov. 2026 »), la même que dans la liste des avis (décision 2026-11-25-dates-formatees-serveur). */
            'date' => DisplayTime::date($review->created_at),
        ];
    }
}
