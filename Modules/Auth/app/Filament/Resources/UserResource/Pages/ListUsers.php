<?php

namespace Modules\Auth\Filament\Resources\UserResource\Pages;

use App\Filament\Pages\Concerns\HasNoBreadcrumbs;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Modules\Auth\Filament\Resources\UserResource;

class ListUsers extends ListRecords
{
    use HasNoBreadcrumbs;

    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
