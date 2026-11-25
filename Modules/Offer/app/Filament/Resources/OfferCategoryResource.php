<?php

namespace Modules\Offer\Filament\Resources;

use App\Filament\Schemas\TranslatableTabs;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Field;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Modules\Offer\Filament\Resources\OfferCategoryResource\Pages\CreateOfferCategory;
use Modules\Offer\Filament\Resources\OfferCategoryResource\Pages\EditOfferCategory;
use Modules\Offer\Filament\Resources\OfferCategoryResource\Pages\ListOfferCategories;
use Modules\Offer\Filament\Resources\OfferCategoryResource\RelationManagers\ChildrenRelationManager;
use Modules\Offer\Models\OfferCategory;

/**
 * Catégories d'offres en arbre (décision
 * 2026-11-18-offres-multilingues-categories-arbre) : la liste montre les racines,
 * triables par glisser-déposer ; la fiche d'une catégorie montre ses
 * sous-catégories, triables elles aussi. Une catégorie qui a des enfants ne se
 * supprime pas.
 */
class OfferCategoryResource extends AbstractOfferResource
{
    protected static ?string $model = OfferCategory::class;

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedTag;

    protected static ?int $navigationSort = 20;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make()
                    ->columns(2)
                    ->schema(self::fields(withParent: true)),
            ]);
    }

    /**
     * Champs d'une catégorie ; le parent se choisit sur sa fiche, il est implicite
     * dans l'onglet « Sous-catégories ».
     *
     * @return list<Component|Field|Tabs>
     */
    public static function fields(bool $withParent): array
    {
        return array_values(array_filter([
            // Traduit : un onglet par langue, chaque nom obligatoire ; dessous, à la modification, le slug de la langue.
            TranslatableTabs::make(fn (string $locale): array => [
                TextInput::make("name.{$locale}")
                    ->label(__('offer::offer.category_resource.name'))
                    ->required()
                    ->maxLength(255),
                // Le slug de la langue, tiré du nom à la création : montré à la modification seulement, en lecture seule
                // (décision 2026-11-25-categories-slug-traduit).
                TextInput::make("slug.{$locale}")
                    ->label(__('offer::offer.category_resource.slug'))
                    ->helperText(__('offer::offer.category_resource.slug_help'))
                    ->hiddenOn('create')
                    ->disabled()
                    ->dehydrated(false),
            ], 'name'),
            ! $withParent ? null : Select::make('parent_id')
                ->label(__('offer::offer.category_resource.parent'))
                ->placeholder(__('offer::offer.category_resource.parent_none'))
                // Ni elle-même ni ses descendants : pas de boucle.
                ->options(fn (?OfferCategory $record): array => OfferCategory::options(except: $record))
                ->in(fn (?OfferCategory $record): array => array_keys(OfferCategory::options(except: $record)))
                ->searchable(),
        ]));
    }

    public static function table(Table $table): Table
    {
        return self::tree($table)
            ->modifyQueryUsing(fn (Builder $query) => $query->whereNull('parent_id'));
    }

    /**
     * Une liste de catégories sœurs : triable, avec le nombre de sous-catégories et d'offres.
     */
    public static function tree(Table $table): Table
    {
        return $table
            ->defaultSort('position')
            ->reorderable('position')
            ->columns([
                TextColumn::make('name')
                    ->label(__('offer::offer.category_resource.name'))
                    ->state(fn (OfferCategory $record): string => (string) $record->getTranslation('name', app()->getLocale())),
                // Le slug de la langue courante.
                TextColumn::make('slug')
                    ->label(__('offer::offer.category_resource.slug'))
                    ->state(fn (OfferCategory $record): string => (string) $record->getTranslation('slug', app()->getLocale())),
                TextColumn::make('children_count')
                    ->label(__('offer::offer.category_resource.children'))
                    ->counts('children'),
                TextColumn::make('offers_count')
                    ->label(__('offer::offer.category_resource.offers_count'))
                    ->counts('offers'),
            ])
            ->recordActions([EditAction::make(), self::deleteAction(DeleteAction::make())])
            ->toolbarActions([BulkActionGroup::make([self::deleteBulkAction()])]);
    }

    /**
     * Supprimer une catégorie qui a des sous-catégories : refusé, avec un message.
     */
    public static function deleteAction(DeleteAction $action): DeleteAction
    {
        return $action->before(function (DeleteAction $action, OfferCategory $record): void {
            if ($record->children()->exists()) {
                Notification::make()->danger()->title(__('offer::offer.category_resource.has_children'))->send();
                $action->cancel();
            }
        });
    }

    public static function deleteBulkAction(): DeleteBulkAction
    {
        return DeleteBulkAction::make()
            ->authorizeIndividualRecords('delete')
            ->before(function (DeleteBulkAction $action, Collection $records): void {
                if ($records->contains(fn (OfferCategory $record): bool => $record->children()->exists())) {
                    Notification::make()->danger()->title(__('offer::offer.category_resource.has_children'))->send();
                    $action->cancel();
                }
            });
    }

    public static function getRelations(): array
    {
        return [
            ChildrenRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListOfferCategories::route('/'),
            'create' => CreateOfferCategory::route('/create'),
            'edit' => EditOfferCategory::route('/{record}/edit'),
        ];
    }

    public static function getRecordTitle(?Model $record): ?string
    {
        return $record instanceof OfferCategory ? $record->path() : null;
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
