<?php

namespace Modules\Auth\Filament\Resources\UserResource\Schemas;

use App\Support\Locales;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Pages\Page;
use Filament\Resources\Pages\CreateRecord;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Modules\Auth\Enums\GenderEnum;
use Modules\Auth\Enums\RoleEnum;
use Modules\Auth\Forms\PersonalInfoForm;
use Modules\Auth\Models\User;
use Modules\Auth\Services\AvatarService;
use Modules\Auth\Support\AuthChannels;
use Modules\Auth\Support\PhoneNumbers;
use Modules\Form\Support\Field;
use Modules\Location\Filament\Forms\SingleCountry;
use Modules\Location\Models\Country;
use Propaganistas\LaravelPhone\Rules\Phone;

abstract class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                static::getGeneralSection(),
                static::getAccessSection(),
            ]);
    }

    public static function getGeneralSection(): Section
    {
        return Section::make(__('auth::user.sections.general'))
            ->columns(2)
            ->schema([
                static::getAvatarField(),
                TextInput::make('name')
                    ->label(__('auth::user.name'))
                    ->helperText(__('auth::user.name_help'))
                    ->maxLength(255)
                    ->unique(ignoreRecord: true)
                    ->required()
                    ->columnSpanFull(),
                TextInput::make('first_name')
                    ->label(__('auth::user.first_name'))
                    ->maxLength(255)
                    ->required(),
                TextInput::make('last_name')
                    ->label(__('auth::user.last_name'))
                    ->maxLength(255)
                    ->required(),
                // Mêmes règles que le formulaire Informations personnelles de l'app.
                Select::make('gender')
                    ->label(__('auth::user.gender'))
                    ->options(GenderEnum::options())
                    ->required(),
                DatePicker::make('birth_date')
                    ->label(__('auth::user.birth_date'))
                    ->native(false)
                    ->displayFormat('d.m.Y')
                    ->minDate(PersonalInfoForm::BIRTH_DATE_MIN)
                    ->maxDate(now()),
                SingleCountry::lock(Select::make('country_id')
                    ->label(__('auth::user.country'))
                    ->options(fn (): array => PersonalInfoForm::countryOptions())
                    ->searchable()
                    ->live()
                    ->afterStateUpdated(fn (Set $set) => $set('region_id', null))),
                // Région : seulement celles du pays choisi, comme dans l'app.
                Select::make('region_id')
                    ->label(__('auth::user.region'))
                    ->options(fn (Get $get): array => collect(PersonalInfoForm::regionOptions())
                        ->filter(fn (array $region): bool => $region['parent'] === (string) $get('country_id'))
                        ->map(fn (array $region): string => $region['label'])
                        ->all())
                    ->disabled(fn (Get $get): bool => blank($get('country_id'))),
                Textarea::make('description')
                    ->label(__('auth::user.description'))
                    ->maxLength(Field::TEXTAREA_MAX_LENGTH)
                    ->rows(4)
                    ->columnSpanFull(),
                // Un compte a un e-mail et/ou un téléphone, jamais aucun des deux ;
                // un canal désactivé dans l'application disparaît (sa valeur reste en base).
                TextInput::make('email')
                    ->label(__('auth::user.email'))
                    ->email()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true)
                    ->visible(AuthChannels::email())
                    ->required(! AuthChannels::phone())
                    ->when(AuthChannels::both(), fn (TextInput $input): TextInput => $input->requiredWithout('phone')),
                SingleCountry::lock(Select::make('phone_country_id')
                    ->label(__('auth::user.phone_country'))
                    ->options(fn (): array => Country::query()->active()->get()
                        ->mapWithKeys(fn (Country $country): array => [$country->getKey() => $country->label().' ('.$country->dial_code.')'])
                        ->sort()
                        ->all())
                    ->searchable()
                    ->visible(AuthChannels::phone())
                    ->requiredWith('phone'))
                    // Sans numéro, pas de pays du téléphone, même choisi d'office.
                    ->dehydrateStateUsing(fn (mixed $state, Get $get): mixed => blank($get('phone')) ? null : $state),
                TextInput::make('phone')
                    ->label(__('auth::user.phone'))
                    ->tel()
                    ->unique(ignoreRecord: true)
                    ->visible(AuthChannels::phone())
                    ->required(! AuthChannels::email())
                    ->when(AuthChannels::both(), fn (TextInput $input): TextInput => $input->requiredWithout('email'))
                    // Numéro vérifié selon le pays choisi, enregistré en E.164.
                    ->rule(fn (Get $get) => (new Phone)->country(Country::query()->find($get('phone_country_id'))?->code ?? 'ZZ'))
                    ->dehydrateStateUsing(fn (?string $state, Get $get): ?string => blank($state) ? null
                        : PhoneNumbers::toE164($state, (string) Country::query()->find($get('phone_country_id'))?->code)),
                Select::make('locale')
                    ->label(__('auth::user.locale'))
                    ->options(Locales::options())
                    ->default(Locales::default())
                    ->selectablePlaceholder(false)
                    ->required(),
                TextInput::make('password')
                    ->label(__('auth::user.password'))
                    ->password()
                    ->revealable()
                    ->maxLength(255)
                    ->required(fn (Page $livewire): bool => $livewire instanceof CreateRecord)
                    ->dehydrated(fn (?string $state): bool => filled($state))
                    ->helperText(fn (Page $livewire): ?string => $livewire instanceof CreateRecord ? null : __('auth::user.password_help')),
            ]);
    }

    /**
     * Photo de profil : mêmes règles et même optimisation que l'API
     * (`AvatarService`) ; l'original n'est pas conservé.
     */
    public static function getAvatarField(): SpatieMediaLibraryFileUpload
    {
        return SpatieMediaLibraryFileUpload::make('avatar')
            ->label(__('auth::user.avatar'))
            ->collection(AvatarService::COLLECTION)
            ->conversion(AvatarService::THUMB)
            ->avatar()
            ->image()
            ->acceptedFileTypes(AvatarService::ACCEPTED_MIME_TYPES)
            ->maxSize(AvatarService::MAX_UPLOAD_KB)
            ->saveUploadedFileUsing(
                fn (TemporaryUploadedFile $file, User $record): string => app(AvatarService::class)
                    ->store($record, $file->getRealPath())
                    ->uuid,
            )
            ->columnSpanFull();
    }

    public static function getAccessSection(): Section
    {
        return Section::make(__('auth::user.sections.access'))
            ->schema([
                Select::make('roles')
                    ->label(__('auth::role.title'))
                    ->relationship(
                        'roles',
                        'name',
                        // Seul un super administrateur peut attribuer le rôle super administrateur.
                        modifyQueryUsing: fn (Builder $query): Builder => auth()->user()?->isSuperAdmin()
                            ? $query
                            : $query->where('name', '!=', RoleEnum::SUPER_ADMIN->value),
                    )
                    ->multiple()
                    ->preload()
                    ->searchable(),
            ]);
    }
}
