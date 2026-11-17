<?php

namespace Modules\Auth\Filament\Resources\UserResource\RelationManagers;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Modules\Auth\Models\User;
use Modules\Location\Filament\Resources\CountryResource\Schemas\AddressForm;
use Modules\Location\Models\UserAddress;
use Modules\Location\Services\AddressService;

/**
 * Adresses d'un utilisateur (`UserAddress`), chacune avec son destinataire,
 * selon la règle d'`AddressService` (décision 2026-11-18-adresse-du-compte-modele) :
 * - ajouter reprend l'adresse identique, ou la crée d'abord ;
 * - modifier change le destinataire sur place et vise l'adresse saisie, reprise
 *   ou créée ; l'adresse précédente, peut-être partagée, n'est jamais modifiée ;
 * - retirer supprime l'entrée, l'adresse reste dans son pays.
 * La rue propose les adresses existantes.
 */
class AddressesRelationManager extends RelationManager
{
    protected static string $relationship = 'addresses';

    public function form(Schema $schema): Schema
    {
        $user = $this->user();

        return AddressForm::configure(
            $schema,
            before: [
                ...array_map(fn ($field) => match ($field->getName()) {
                    // À l'ajout, le destinataire est d'abord l'utilisateur lui-même.
                    'gender' => $field->default($user->gender?->value),
                    'first_name' => $field->default($user->first_name),
                    'last_name' => $field->default($user->last_name),
                    default => $field,
                }, AddressForm::recipient()),
                AddressForm::country()->columnSpanFull(),
            ],
            suggestions: true,
        );
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('first_name')
            ->modifyQueryUsing(fn ($query) => $query->with('address.country'))
            ->columns([
                TextColumn::make('recipient')
                    ->label(__('location::address.recipient'))
                    ->state(fn (UserAddress $record): string => $record->recipient()),
                TextColumn::make('address.street')
                    ->label(__('location::address.street'))
                    ->searchable(),
                TextColumn::make('address.zip')
                    ->label(__('location::address.zip'))
                    ->searchable(),
                TextColumn::make('address.city')
                    ->label(__('location::address.city'))
                    ->searchable(),
                TextColumn::make('address.country.name')
                    ->label(__('location::address.country'))
                    ->state(fn (UserAddress $record): string => (string) $record->address->country?->getTranslation('name', app()->getLocale())),
            ])
            ->headerActions([
                CreateAction::make()
                    ->using(fn (array $data): UserAddress => app(AddressService::class)->attach($this->user(), $data)),
            ])
            ->recordActions([
                EditAction::make()
                    ->fillForm(fn (UserAddress $record): array => [
                        'gender' => $record->gender?->value,
                        'first_name' => $record->first_name,
                        'last_name' => $record->last_name,
                        ...$record->address->only(AddressService::ADDRESS_FIELDS),
                        'country_id' => (string) $record->address->country_id,
                    ])
                    ->using(fn (UserAddress $record, array $data): UserAddress => app(AddressService::class)->update($record, $data)),
                DeleteAction::make()
                    ->label(__('location::address.detach')),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()->authorizeIndividualRecords('delete'),
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
