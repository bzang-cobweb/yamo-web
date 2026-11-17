<?php

namespace App\Filament\Resources\SettingResource\Pages;

use App\Filament\Resources\SettingResource;
use App\Models\Setting;
use Filament\Resources\Pages\EditRecord;

/**
 * Page « Application » : modifie l'unique ligne des réglages, sans identifiant
 * dans l'adresse.
 */
class EditSettings extends EditRecord
{
    protected static string $resource = SettingResource::class;

    public function mount(int|string|null $record = null): void
    {
        parent::mount(Setting::current()->getKey());
    }

    public function getTitle(): string
    {
        return __('settings.title');
    }

    public function getBreadcrumbs(): array
    {
        return [];
    }

    protected function getRedirectUrl(): ?string
    {
        return null;
    }
}
