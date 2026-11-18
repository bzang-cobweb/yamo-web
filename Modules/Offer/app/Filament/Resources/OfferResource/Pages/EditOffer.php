<?php

namespace Modules\Offer\Filament\Resources\OfferResource\Pages;

use App\Filament\Pages\Concerns\HasRecordTitleBreadcrumbs;
use Filament\Actions\DeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;
use Modules\Offer\Domains\DomainRegistry;
use Modules\Offer\Filament\Resources\OfferResource;
use Modules\Offer\Services\OfferService;

class EditOffer extends EditRecord
{
    use HasRecordTitleBreadcrumbs;

    protected static string $resource = OfferResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
            RestoreAction::make(),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        $domain = app(DomainRegistry::class)->require();
        $data['currency'] = filled($data['price'] ?? null) ? $domain->currency() : null;
        $data['details'] = app(OfferService::class)->typedDetails($domain, (array) ($data['details'] ?? []));

        return $data;
    }
}
