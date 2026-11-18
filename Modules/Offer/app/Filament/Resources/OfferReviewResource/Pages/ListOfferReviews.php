<?php

namespace Modules\Offer\Filament\Resources\OfferReviewResource\Pages;

use App\Filament\Pages\Concerns\HasNoBreadcrumbs;
use Filament\Resources\Pages\ListRecords;
use Modules\Offer\Filament\Resources\OfferReviewResource;

/**
 * Les avis, sans bouton de création.
 */
class ListOfferReviews extends ListRecords
{
    use HasNoBreadcrumbs;

    protected static string $resource = OfferReviewResource::class;
}
