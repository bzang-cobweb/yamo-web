<?php

namespace Modules\Auth\Filament\Resources\UserResource\RelationManagers;

use Bza\Form\Support\Field;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Modules\Auth\Models\User;
use Modules\Auth\Models\UserAddress;
use Modules\Auth\Services\UserAddressService;
use Modules\Location\Filament\Resources\CountryResource\Schemas\AddressForm;
use Modules\Location\Services\AddressService;

/**
 * Adresses d'un utilisateur (`UserAddress`), chacune avec son destinataire,
 * selon la règle d'`UserAddressService` (décisions 2026-11-18-adresse-du-compte-modele
 * et 2026-11-18-useraddress-dans-auth) :
 * - ajouter reprend l'adresse identique, ou la crée d'abord ;
 * - modifier change le destinataire sur place et vise l'adresse saisie, reprise
 *   ou créée ; l'adresse précédente, peut-être partagée, n'est jamais modifiée ;
 * - retirer supprime l'entrée, l'adresse reste dans sa région.
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
                ...self::recipient($user),
            ],
            suggestions: true,
            after: [
                Textarea::make('indication')
                    ->label(__('auth::address.indication'))
                    ->placeholder(__('auth::address.indication_placeholder'))
                    ->maxLength(Field::TEXTAREA_MAX_LENGTH)
                    ->rows(3)
                    ->columnSpanFull(),
            ],
        );
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('first_name')
            ->modifyQueryUsing(fn ($query) => $query->with('address.region.country'))
            ->columns([
                TextColumn::make('recipient')
                    ->label(__('auth::address.recipient'))
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
                // La région et son pays (décision 2026-11-25-adresse-sans-pays).
                TextColumn::make('address.region.name')
                    ->label(__('location::address.region'))
                    ->state(fn (UserAddress $record): string => trim((string) $record->address->region?->getTranslation('name', app()->getLocale())).' ('.$record->address->region?->country?->label().')'),
            ])
            ->headerActions([
                CreateAction::make()
                    ->using(fn (array $data): UserAddress => app(UserAddressService::class)->attach($this->user(), $data)),
            ])
            ->recordActions([
                EditAction::make()
                    ->fillForm(fn (UserAddress $record): array => [
                        'first_name' => $record->first_name,
                        'last_name' => $record->last_name,
                        'indication' => $record->indication,
                        ...$record->address->only(AddressService::ADDRESS_FIELDS),
                        'region_id' => (string) $record->address->region_id,
                    ])
                    ->using(fn (UserAddress $record, array $data): UserAddress => app(UserAddressService::class)->update($record, $data)),
                DeleteAction::make()
                    ->label(__('auth::address.detach')),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()->authorizeIndividualRecords('delete'),
                ]),
            ]);
    }

    /**
     * Prénom et nom du destinataire ; à l'ajout, c'est d'abord
     * l'utilisateur lui-même.
     *
     * @return list<Component>
     */
    private static function recipient(User $user): array
    {
        return [
            TextInput::make('first_name')
                ->label(__('auth::user.first_name'))
                ->default($user->first_name)
                ->required()
                ->maxLength(255),
            TextInput::make('last_name')
                ->label(__('auth::user.last_name'))
                ->default($user->last_name)
                ->required()
                ->maxLength(255),
        ];
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
