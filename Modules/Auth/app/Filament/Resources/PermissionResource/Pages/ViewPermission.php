<?php

namespace Modules\Auth\Filament\Resources\PermissionResource\Pages;

use Filament\Resources\Pages\ViewRecord;
use Modules\Auth\Filament\Resources\PermissionResource;

class ViewPermission extends ViewRecord
{
    protected static string $resource = PermissionResource::class;

    public function getBreadcrumb(): string
    {
        return $this->record->name ?? parent::getBreadcrumb();
    }
}
