<?php

namespace Modules\Auth\Filament\Resources\UserResource\Schemas;

use Bza\Form\Support\Field;
use Bza\Form\Support\Locales;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Pages\Page;
use Filament\Resources\Pages\CreateRecord;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Modules\Auth\Enums\RoleEnum;
use Modules\Auth\Forms\PersonalInfoForm;
use Modules\Auth\Models\User;
use Modules\Auth\Services\AvatarService;
use Modules\Auth\Services\IdentityDocumentService;
use Modules\Auth\Support\AuthChannels;
use Modules\Auth\Support\PhoneNumbers;
use Modules\Location\Filament\Forms\SingleCountry;
use Modules\Location\Models\Country;
use Propaganistas\LaravelPhone\Rules\Phone;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

abstract class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                // Des onglets plutôt que des sections (décision 2026-11-25-utilisateur-onglets).
                Tabs::make('user')
                    ->columnSpanFull()
                    ->persistTabInQueryString()
                    ->tabs([
                        static::getGeneralTab(),
                        static::getIdentityTab(),
                        static::getAccessTab(),
                    ]),
            ]);
    }

    public static function getGeneralTab(): Tab
    {
        return Tab::make(__('auth::user.sections.general'))
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
                    // Le nom des régions du pays choisi (décision 2026-11-25-region-label-partout).
                    ->label(fn (Get $get): string => Country::query()->find($get('country_id'))?->regionLabel() ?? (string) __('auth::user.region'))
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
            ->conversion('detail')
            ->avatar()
            ->image()
            ->acceptedFileTypes(AvatarService::acceptedMimeTypes())
            ->maxSize(AvatarService::maxUploadKb())
            // Au moins la taille enregistrée (décision 2026-11-24-image-taille-minimale-enregistree).
            ->rules(AvatarService::uploadRules())
            ->saveUploadedFileUsing(
                fn (TemporaryUploadedFile $file, User $record): string => app(AvatarService::class)
                    ->store($record, $file->getRealPath())
                    ->uuid,
            )
            ->columnSpanFull();
    }

    /**
     * L'identification (décision 2026-11-25-identification) : la date, remplie par « Valider l'identité » en haut de
     * la page, et les pièces d'identité envoyées depuis l'app, ouvertes par un lien signé de quelques minutes (elles
     * restent sur le disque privé). À la modification seulement.
     */
    public static function getIdentityTab(): Tab
    {
        return Tab::make(__('auth::identity.admin.section'))
            ->visible(fn (Page $livewire): bool => ! $livewire instanceof CreateRecord)
            ->schema([
                TextEntry::make('identified_at')
                    ->label(__('auth::identity.admin.identified_at'))
                    ->dateTime('d.m.Y H:i')
                    ->placeholder('—'),
                TextEntry::make('identity_documents')
                    ->label(__('auth::identity.admin.documents'))
                    ->state(fn (?User $record): array => $record?->getMedia(IdentityDocumentService::COLLECTION)
                        ->map(fn (Media $media): string => '<a href="'.e($media->getTemporaryUrl(now()->addMinutes(10))).'" target="_blank" rel="noopener" class="underline">'.e($media->file_name).'</a>')
                        ->all() ?? [])
                    ->html()
                    ->listWithLineBreaks()
                    ->placeholder(__('auth::identity.admin.no_documents')),
            ]);
    }

    public static function getAccessTab(): Tab
    {
        return Tab::make(__('auth::user.sections.access'))
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
