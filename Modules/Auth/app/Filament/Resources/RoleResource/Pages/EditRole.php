<?php

namespace Modules\Auth\Filament\Resources\RoleResource\Pages;

use App\Filament\Pages\Concerns\HasRecordTitleBreadcrumbs;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Modules\Auth\Filament\Resources\RoleResource;

class EditRole extends EditRecord
{
    use HasRecordTitleBreadcrumbs;

    protected static string $resource = RoleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
