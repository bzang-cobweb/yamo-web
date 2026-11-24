<?php

namespace Modules\Partner\Filament\Resources;

use App\Filament\AbstractResource;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Modules\Partner\Enums\PartnerStatusEnum;
use Modules\Partner\Filament\Resources\PartnerResource\Pages\CreatePartner;
use Modules\Partner\Filament\Resources\PartnerResource\Pages\EditPartner;
use Modules\Partner\Filament\Resources\PartnerResource\Pages\ListPartners;
use Modules\Partner\Filament\Resources\PartnerResource\RelationManagers\UsersRelationManager;
use Modules\Partner\Filament\Resources\PartnerResource\Schemas\PartnerForm;
use Modules\Partner\Filament\Resources\PartnerResource\Tables\PartnerTable;
use Modules\Partner\Models\Partner;
use Modules\Partner\Services\PartnerService;

/**
 * Les partenaires de l'accueil de l'app (décision 2026-11-23-module-partenaires) : ceux qui paient pour un
 * espace, leur campagne et leurs compteurs.
 */
class PartnerResource extends AbstractResource
{
    protected static ?string $model = Partner::class;

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingStorefront;

    protected static ?int $navigationSort = 30;

    protected static ?string $recordTitleAttribute = 'name';

    /**
     * « Valider » : un partenaire en validation passe en « Publié », pour qui peut le modifier ; ses comptes reçoivent
     * « Partenaire accepté » (décision 2026-11-24-partenaires-validation-back-office). Sur la liste et la page du partenaire.
     */
    public static function validateAction(): Action
    {
        return Action::make('validate')
            ->label(__('partner::partner.validate.label'))
            ->icon(Heroicon::OutlinedCheckCircle)
            ->color('success')
            ->visible(fn (Partner $record): bool => $record->status === PartnerStatusEnum::PENDING && (auth()->user()?->can('update', $record) ?? false))
            ->requiresConfirmation()
            ->modalHeading(__('partner::partner.validate.heading'))
            ->modalDescription(__('partner::partner.validate.description'))
            ->action(function (Partner $record): void {
                app(PartnerService::class)->accept($record);
                Notification::make()->title(__('partner::partner.validate.done'))->success()->send();
            });
    }

    public static function form(Schema $schema): Schema
    {
        return PartnerForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PartnerTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            // Les comptes rattachés (décision 2026-11-24-partenaires-par-les-membres).
            UsersRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPartners::route('/'),
            'create' => CreatePartner::route('/create'),
            'edit' => EditPartner::route('/{record}/edit'),
        ];
    }

    public static function getModelLabel(): string
    {
        return __('partner::partner.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('partner::partner.plural');
    }
}
