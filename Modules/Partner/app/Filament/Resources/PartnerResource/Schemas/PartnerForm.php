<?php

namespace Modules\Partner\Filament\Resources\PartnerResource\Schemas;

use App\Filament\Schemas\TranslatableTabs;
use App\Support\Locales;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Modules\Auth\Support\PhoneNumbers;
use Modules\Form\Rules\MinImageSide;
use Modules\Location\Models\Country;
use Modules\Partner\Enums\PartnerStatusEnum;
use Modules\Partner\Models\Partner;
use Modules\Partner\Services\PartnerLogoService;

/**
 * Le partenaire, en onglets (décisions 2026-11-23-module-partenaires et 2026-11-23-partenaires-nom-traduit) :
 * qui il est et ce que l'app en montre dans chaque langue, sa campagne, son contact et ses compteurs.
 */
abstract class PartnerForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Tabs::make('partner')
                    ->tabs([
                        Tab::make(__('partner::partner.sections.identity'))
                            ->columns(2)
                            ->schema([
                                // Optimisé comme depuis l'app : WebP, la plus grande conversion au plus, l'original n'est pas gardé (décision
                                // 2026-11-24-partenaire-logo-optimise).
                                SpatieMediaLibraryFileUpload::make('logo')
                                    ->label(__('partner::partner.fields.logo'))
                                    ->collection(Partner::LOGO)
                                    ->image()
                                    ->acceptedFileTypes(PartnerLogoService::acceptedMimeTypes())
                                    ->maxSize(PartnerLogoService::maxUploadKb())
                                    // Au moins la plus grande conversion (décision 2026-11-24-logo-taille-minimale).
                                    ->rule(new MinImageSide(PartnerLogoService::minUploadSize()))
                                    ->helperText(__('partner::partner.fields.logo_help', ['pixels' => PartnerLogoService::minUploadSize()]))
                                    ->saveUploadedFileUsing(
                                        fn (TemporaryUploadedFile $file, Partner $record): string => app(PartnerLogoService::class)
                                            ->store($record, $file->getRealPath())
                                            ->uuid,
                                    ),
                                // Un onglet par langue : le nom, le slogan et la description.
                                TranslatableTabs::make(fn (string $locale): array => [
                                    TextInput::make("name.{$locale}")
                                        ->label(__('partner::partner.fields.name'))
                                        // Obligatoire dans la langue par défaut, comme le titre d'une offre.
                                        ->required($locale === Locales::default())
                                        ->maxLength(255),
                                    // Facultatif (décision 2026-11-24-partenaire-slogan).
                                    TextInput::make("slogan.{$locale}")
                                        ->label(__('partner::partner.fields.slogan'))
                                        ->maxLength(120),
                                    Textarea::make("description.{$locale}")
                                        ->label(__('partner::partner.fields.description'))
                                        // Obligatoire dans la langue par défaut, comme le nom (décision
                                        // 2026-11-24-partenaire-description-obligatoire).
                                        ->required($locale === Locales::default())
                                        ->rows(3)
                                        ->maxLength(500),
                                ], 'content')->columnSpanFull(),
                            ]),
                        Tab::make(__('partner::partner.sections.campaign'))
                            ->columns(2)
                            ->schema([
                                // Décision 2026-11-24-partenaires-statuts.
                                Select::make('status')
                                    ->label(__('partner::partner.fields.status'))
                                    ->options(PartnerStatusEnum::options())
                                    ->default(PartnerStatusEnum::PENDING->value)
                                    ->required()
                                    ->columnSpanFull(),
                                DateTimePicker::make('starts_at')
                                    ->label(__('partner::partner.fields.starts_at')),
                                DateTimePicker::make('expires_at')
                                    ->label(__('partner::partner.fields.expires_at')),
                            ]),
                        Tab::make(__('partner::partner.sections.contact'))
                            ->columns(2)
                            ->schema([
                                // Le pays en premier (décision 2026-11-23-partenaire-pays-dans-contact).
                                Select::make('country_id')
                                    ->label(__('partner::partner.fields.country'))
                                    ->relationship('country', 'code')
                                    ->getOptionLabelFromRecordUsing(fn (Country $country): string => $country->label())
                                    ->required()
                                    ->searchable()
                                    ->preload()
                                    ->columnSpanFull(),
                                TextInput::make('contact_email')
                                    ->label(__('partner::partner.fields.contact_email'))
                                    ->email()
                                    ->maxLength(255),
                                // L'indicatif du pays du partenaire devant le numéro ; enregistré au format international
                                // (décision 2026-11-24-partenaire-telephone-pays).
                                TextInput::make('contact_phone')
                                    ->label(__('partner::partner.fields.contact_phone'))
                                    ->tel()
                                    ->live(onBlur: true)
                                    ->prefix(fn (Get $get): ?string => Country::query()->find($get('country_id'))?->dial_code)
                                    ->formatStateUsing(fn (?string $state): ?string => blank($state) ? null : PhoneNumbers::national($state))
                                    ->rule(fn (Get $get): string => 'phone:'.(Country::query()->find($get('country_id'))?->code ?? 'INTERNATIONAL'))
                                    ->dehydrateStateUsing(fn (?string $state, Get $get): ?string => blank($state) ? null : PhoneNumbers::toE164($state, (string) Country::query()->find($get('country_id'))?->code))
                                    ->maxLength(30),
                                // Le lien et le libellé de son bouton, un onglet par langue (décision 2026-11-24-partenaire-lien-dans-contact).
                                TranslatableTabs::make(fn (string $locale): array => [
                                    TextInput::make("url.{$locale}")
                                        ->label(__('partner::partner.fields.url'))
                                        ->url()
                                        ->maxLength(2048),
                                    TextInput::make("cta_label.{$locale}")
                                        ->label(__('partner::partner.fields.cta_label'))
                                        ->maxLength(60),
                                ], 'links')->columnSpanFull(),
                            ]),
                        // Lus seulement : l'app les tient à jour.
                        Tab::make(__('partner::partner.sections.stats'))
                            ->columns(2)
                            ->visibleOn('edit')
                            ->schema([
                                TextInput::make('views_count')
                                    ->label(__('partner::partner.fields.views_count'))
                                    ->disabled()
                                    ->dehydrated(false),
                                TextInput::make('clicks_count')
                                    ->label(__('partner::partner.fields.clicks_count'))
                                    ->disabled()
                                    ->dehydrated(false),
                            ]),
                    ]),
            ]);
    }
}
