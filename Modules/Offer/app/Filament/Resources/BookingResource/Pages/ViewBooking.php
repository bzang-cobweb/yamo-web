<?php

namespace Modules\Offer\Filament\Resources\BookingResource\Pages;

use App\Filament\Pages\Concerns\HasRecordTitleBreadcrumbs;
use Filament\Resources\Pages\ViewRecord;
use Modules\Offer\Filament\Resources\BookingResource;

class ViewBooking extends ViewRecord
{
    use HasRecordTitleBreadcrumbs;

    protected static string $resource = BookingResource::class;
}
