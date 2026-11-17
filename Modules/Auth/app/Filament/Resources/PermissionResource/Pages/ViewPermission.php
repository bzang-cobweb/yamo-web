<?php

namespace Modules\Auth\Filament\Resources\PermissionResource\Pages;

use App\Filament\Pages\Concerns\HasRecordTitleBreadcrumbs;
use Filament\Resources\Pages\ViewRecord;
use Modules\Auth\Filament\Resources\PermissionResource;

class ViewPermission extends ViewRecord
{
    use HasRecordTitleBreadcrumbs;

    protected static string $resource = PermissionResource::class;
}
