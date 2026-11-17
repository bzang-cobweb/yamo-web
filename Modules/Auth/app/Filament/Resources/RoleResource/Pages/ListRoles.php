<?php

namespace Modules\Auth\Filament\Resources\RoleResource\Pages;

use App\Filament\Pages\Concerns\HasNoBreadcrumbs;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Modules\Auth\Filament\Resources\RoleResource;

class ListRoles extends ListRecords
{
    use HasNoBreadcrumbs;

    protected static string $resource = RoleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
