<?php

namespace Modules\Location\Filament\Resources\CountryResource\Pages;

use App\Filament\Pages\Concerns\HasRecordTitleBreadcrumbs;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\QueryException;
use Modules\Location\Filament\Resources\CountryResource;
use Modules\Location\Models\Country;

class EditCountry extends EditRecord
{
    use HasRecordTitleBreadcrumbs;

    protected static string $resource = CountryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Un pays encore utilisé (par des offres) est gardé par la base : un message
            // l'explique au lieu d'une page d'erreur (décision 2026-11-19-pays-obligatoire-offre).
            DeleteAction::make()
                ->using(function (Country $record): bool {
                    try {
                        return (bool) $record->delete();
                    } catch (QueryException $exception) {
                        // 23000 : une contrainte d'intégrité ; toute autre erreur remonte.
                        if ((string) $exception->getCode() !== '23000') {
                            throw $exception;
                        }

                        return false;
                    }
                })
                ->failureNotificationTitle(__('location::country.delete_in_use')),
        ];
    }
}
