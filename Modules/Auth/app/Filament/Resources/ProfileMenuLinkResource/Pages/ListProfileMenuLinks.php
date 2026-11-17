<?php

namespace Modules\Auth\Filament\Resources\ProfileMenuLinkResource\Pages;

use App\Filament\Pages\Concerns\HasNoBreadcrumbs;
use Filament\Resources\Pages\ListRecords;
use Modules\Auth\Filament\Resources\ProfileMenuLinkResource;

class ListProfileMenuLinks extends ListRecords
{
    use HasNoBreadcrumbs;

    protected static string $resource = ProfileMenuLinkResource::class;

    public function getSubheading(): string
    {
        return __('auth::profile_menu.help');
    }
}
