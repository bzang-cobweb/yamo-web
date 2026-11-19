<?php

namespace Modules\Offer\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Pagination\CursorPaginator;
use Modules\Offer\Models\OfferReview;

/**
 * Une page des avis d'une offre, du plus récent au plus ancien (décision
 * 2026-11-19-nuit-tous-les-avis).
 *
 * @property CursorPaginator<int, OfferReview> $resource
 */
class OfferReviewPageResource extends JsonResource
{
    /**
     * @return array{items: list<array{id: string, author: string, rating: int, comment: string, created_at: string|null}>, next_cursor: string|null}
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
        ];
    }
}
