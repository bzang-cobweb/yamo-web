<?php

namespace Modules\Location\Filament\Resources\CountryResource\RelationManagers;

use App\Filament\AbstractResource;
use Filament\Actions\CreateAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Modules\Location\Filament\Resources\CountryResource\Schemas\AddressForm;
use Modules\Location\Models\Address;
use Modules\Location\Models\Country;
use Modules\Location\Rules\UniqueAddress;

/**
 * Adresses d'un pays (celles de ses régions, décision 2026-11-25-adresse-sans-pays), gérées depuis sa fiche. Une
 * adresse n'existe qu'une fois dans sa région :
 * un doublon (même rue, NPA et ville, sans casse ni espaces) est refusé
 * (décision 2026-11-18-adresses-liste-generique). Chaque action passe par
 * `AddressPolicy`.
 */
class AddressesRelationManager extends RelationManager
{
    protected static string $relationship = 'addresses';

    public function form(Schema $schema): Schema
    {
        /** @var Country $country */
        $country = $this->getOwnerRecord();

        // Ses régions ; l'unicité dans la région choisie (décision 2026-11-25-adresse-sans-pays).
        return AddressForm::configure(
            $schema,
            streetRule: fn (?Address $record, Get $get): UniqueAddress => new UniqueAddress($get('region_id'), $get('zip'), $get('city'), $record),
            country: (int) $country->getKey(),
        );
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('street')
            ->defaultSort('street')
            ->columns([
                TextColumn::make('street')
                    ->label(__('location::address.street'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('zip')
                    ->label(__('location::address.zip'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('city')
                    ->label(__('location::address.city'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('region.name')
                    // Le nom des régions du pays (décision 2026-11-25-region-label-partout).
                    ->label(fn (): string => $this->getOwnerRecord() instanceof Country ? $this->getOwnerRecord()->regionLabel() : (string) __('location::address.region'))
                    ->state(fn (Address $record): string => trim((string) $record->region?->getTranslation('name', app()->getLocale()))),
                TextColumn::make('latitude')
                    ->label(__('location::address.latitude'))
                    ->toggleable(),
                TextColumn::make('longitude')
                    ->label(__('location::address.longitude'))
                    ->toggleable(),
            ])
            ->headerActions([
                // Les adresses du pays passent par ses régions : la création vise la région choisie.
                CreateAction::make()
                    ->using(fn (array $data): Address => Address::query()->create($data)),
            ])
            ->recordActions(AbstractResource::getActions())
            ->toolbarActions(AbstractResource::getBulkActions());
    }

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        /** @var Country $ownerRecord */
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
