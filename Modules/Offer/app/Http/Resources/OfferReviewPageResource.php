<?php

namespace Modules\Offer\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Pagination\CursorPaginator;
use Modules\Offer\Models\Offer;
use Modules\Offer\Models\OfferReview;

/**
 * Une page des avis d'une offre, du plus récent au plus ancien (décision
 * 2026-11-19-nuit-tous-les-avis).
 *
 * @property CursorPaginator<int, OfferReview> $resource
 */
class OfferReviewPageResource extends JsonResource
{
    /** L'offre des avis : son titre de page et son nombre d'avis (décision 2026-11-25-liste-avis-titre-nombre). */
    private ?Offer $offer = null;

    public function forOffer(Offer $offer): static
    {
        $this->offer = $offer;

        return $this;
    }

    /**
     * @return array{items: list<array{id: string, author: string, rating: int, comment: string, created_at: string|null}>, next_cursor: string|null, title: string|null, heading: string|null}
     */
    public function toArray(Request $request): array
    {
        $cursor = $this->resource->nextCursor();
        $items = [];
        foreach ($this->resource->items() as $review) {
            $items[] = (new OfferReviewResource($review))->toArray($request);
        }

        return [
            'items' => $items,
            /** Curseur de la page suivante, ou `null` à la dernière. */
            'next_cursor' => $cursor === null ? null : (string) $cursor->encode(),
            // « Avis (4,7) », le titre de la page, comme la section de la fiche ; « 3 avis », avant la liste.
            'title' => $this->offer === null ? null : $this->offer->reviewsTitle(),
            'heading' => $this->offer === null || $this->offer->reviews_count === 0 ? null : trans_choice('offer::offer.reviews_count', $this->offer->reviews_count, ['count' => $this->offer->reviews_count]),
        ];
    }
}
