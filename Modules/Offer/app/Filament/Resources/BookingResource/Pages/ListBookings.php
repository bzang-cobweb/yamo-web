<?php

namespace Modules\Offer\Filament\Resources\BookingResource\Pages;

use App\Filament\Pages\Concerns\HasNoBreadcrumbs;
use Filament\Resources\Pages\ListRecords;
use Modules\Offer\Filament\Resources\BookingResource;

class ListBookings extends ListRecords
{
    use HasNoBreadcrumbs;

    protected static string $resource = BookingResource::class;
}
