<?php

namespace Modules\Partner\Filament\Resources;

use App\Filament\AbstractResource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Modules\Partner\Filament\Resources\PartnerResource\Pages\CreatePartner;
use Modules\Partner\Filament\Resources\PartnerResource\Pages\EditPartner;
use Modules\Partner\Filament\Resources\PartnerResource\Pages\ListPartners;
use Modules\Partner\Filament\Resources\PartnerResource\RelationManagers\UsersRelationManager;
use Modules\Partner\Filament\Resources\PartnerResource\Schemas\PartnerForm;
use Modules\Partner\Filament\Resources\PartnerResource\Tables\PartnerTable;
use Modules\Partner\Models\Partner;

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
