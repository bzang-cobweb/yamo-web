<?php

namespace Modules\Auth\Filament\Resources\RoleResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Modules\Auth\Filament\Resources\RoleResource;

class CreateRole extends CreateRecord
{
    protected static string $resource = RoleResource::class;
}
