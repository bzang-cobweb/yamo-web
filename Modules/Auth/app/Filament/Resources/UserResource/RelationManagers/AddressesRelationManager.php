<?php

namespace Modules\Auth\Filament\Resources\UserResource\RelationManagers;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DetachAction;
use Filament\Actions\DetachBulkAction;
use Filament\Actions\EditAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Modules\Auth\Models\User;
use Modules\Location\Filament\Resources\CountryResource\Schemas\AddressForm;
use Modules\Location\Models\Address;
use Modules\Location\Services\AddressService;

/**
 * Adresses d'un utilisateur, sur le pivot `address_user`, selon la règle
 * d'`AddressService` (décision 2026-11-18-adresses-liste-generique) :
 * - ajouter rattache l'adresse identique, ou la crée d'abord ;
 * - modifier rattache la nouvelle saisie de la même façon et détache
 *   l'ancienne, qui n'est jamais modifiée : d'autres comptes la partagent ;
 * - retirer ne supprime que le pivot.
 */
class AddressesRelationManager extends RelationManager
{
    protected static string $relationship = 'addresses';

    public function form(Schema $schema): Schema
    {
        return AddressForm::configure($schema, before: [AddressForm::country()->columnSpanFull()]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('street')
            ->columns([
                TextColumn::make('street')
                    ->label(__('location::address.street'))
                    ->searchable(),
                TextColumn::make('zip')
                    ->label(__('location::address.zip'))
                    ->searchable(),
                TextColumn::make('city')
                    ->label(__('location::address.city'))
                    ->searchable(),
                TextColumn::make('country.name')
                    ->label(__('location::address.country'))
                    ->state(fn (Address $record): string => (string) $record->country?->getTranslation('name', app()->getLocale())),
            ])
            ->headerActions([
                CreateAction::make()
                    ->using(fn (array $data): Address => app(AddressService::class)->attach($this->user(), $data)),
            ])
            ->recordActions([
                EditAction::make()
                    ->using(fn (Address $record, array $data): Address => app(AddressService::class)->replace($this->user(), $record, $data)),
                DetachAction::make()
                    ->label(__('location::address.detach')),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DetachBulkAction::make()->authorizeIndividualRecords('detach'),
                ]),
            ]);
    }

    private function user(): User
    {
        /** @var User */
        return $this->getOwnerRecord();
    }

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        /** @var User $ownerRecord */
        return __('location::address.title').' ('.$ownerRecord->addresses()->count().')';
    }

    public static function getModelLabel(): string
    {
        return __('location::address.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('location::address.plural');
    }
}
