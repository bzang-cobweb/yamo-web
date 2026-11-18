<?php

namespace Modules\Offer\Filament\Resources\OfferCategoryResource\RelationManagers;

use Filament\Actions\CreateAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Modules\Offer\Filament\Resources\OfferCategoryResource;
use Modules\Offer\Models\OfferCategory;

/**
 * Sous-catégories d'une catégorie : créées, triées par glisser-déposer, ouvertes
 * pour descendre dans l'arbre (décision 2026-11-18-offres-multilingues-categories-arbre).
 */
class ChildrenRelationManager extends RelationManager
{
    protected static string $relationship = 'children';

    public function form(Schema $schema): Schema
    {
        return $schema->columns(2)->components(OfferCategoryResource::fields(withParent: false));
    }

    public function table(Table $table): Table
    {
        return OfferCategoryResource::tree($table)
            ->recordUrl(fn (OfferCategory $record): string => OfferCategoryResource::getUrl('edit', ['record' => $record]))
            ->headerActions([
                CreateAction::make()
                    // À la fin de ses sœurs.
                    ->mutateDataUsing(fn (array $data): array => [...$data, 'position' => (int) $this->getOwnerRecord()->children()->max('position') + 1]),
            ]);
    }

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        /** @var OfferCategory $ownerRecord */
        return __('offer::offer.category_resource.children').' ('.$ownerRecord->children()->count().')';
    }

    public static function getModelLabel(): string
    {
        return __('offer::offer.category_resource.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('offer::offer.category_resource.plural');
    }
}
