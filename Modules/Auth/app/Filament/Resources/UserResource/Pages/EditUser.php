<?php

namespace Modules\Auth\Filament\Resources\UserResource\Pages;

use App\Filament\Pages\Concerns\HasRecordTitleBreadcrumbs;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Modules\Auth\Filament\Resources\UserResource;

class EditUser extends EditRecord
{
    use HasRecordTitleBreadcrumbs;

    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
