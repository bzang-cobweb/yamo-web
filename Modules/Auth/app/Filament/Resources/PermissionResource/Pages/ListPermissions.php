<?php

namespace Modules\Auth\Filament\Resources\PermissionResource\Pages;

use Filament\Resources\Pages\ListRecords;
use Modules\Auth\Filament\Resources\PermissionResource;

class ListPermissions extends ListRecords
{
    protected static string $resource = PermissionResource::class;
}
