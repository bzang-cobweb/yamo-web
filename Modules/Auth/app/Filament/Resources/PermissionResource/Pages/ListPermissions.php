<?php

namespace Modules\Auth\Filament\Resources\PermissionResource\Pages;

use App\Filament\Pages\Concerns\HasNoBreadcrumbs;
use Filament\Resources\Pages\ListRecords;
use Modules\Auth\Filament\Resources\PermissionResource;

class ListPermissions extends ListRecords
{
    use HasNoBreadcrumbs;

    protected static string $resource = PermissionResource::class;
}
