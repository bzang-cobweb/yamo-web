<?php

namespace Modules\Partner\Filament\Resources\PartnerResource\Schemas;

use App\Filament\Schemas\TranslatableTabs;
use App\Support\Locales;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Modules\Location\Models\Country;
use Modules\Partner\Models\Partner;

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
                                Select::make('country_id')
                                    ->label(__('partner::partner.fields.country'))
                                    ->relationship('country', 'code')
                                    ->getOptionLabelFromRecordUsing(fn (Country $country): string => $country->label())
                                    ->required()
                                    ->searchable()
                                    ->preload(),
                                SpatieMediaLibraryFileUpload::make('logo')
                                    ->label(__('partner::partner.fields.logo'))
                                    ->collection(Partner::LOGO)
                                    ->image()
                                    ->maxSize(2048),
                                // Un onglet par langue : le nom, la description, le lien et le libellé du bouton.
                                TranslatableTabs::make(fn (string $locale): array => [
                                    TextInput::make("name.{$locale}")
                                        ->label(__('partner::partner.fields.name'))
                                        // Obligatoire dans la langue par défaut, comme le titre d'une offre.
                                        ->required($locale === Locales::default())
                                        ->maxLength(255),
                                    Textarea::make("description.{$locale}")
                                        ->label(__('partner::partner.fields.description'))
                                        ->rows(3)
                                        ->maxLength(500),
                                    TextInput::make("url.{$locale}")
                                        ->label(__('partner::partner.fields.url'))
                                        ->url()
                                        ->maxLength(2048),
                                    TextInput::make("cta_label.{$locale}")
                                        ->label(__('partner::partner.fields.cta_label'))
                                        ->maxLength(60),
                                ], 'content')->columnSpanFull(),
                            ]),
                        Tab::make(__('partner::partner.sections.campaign'))
                            ->columns(2)
                            ->schema([
                                Toggle::make('published')
                                    ->label(__('partner::partner.fields.published'))
                                    ->columnSpanFull(),
                                DateTimePicker::make('starts_at')
                                    ->label(__('partner::partner.fields.starts_at')),
                                DateTimePicker::make('expires_at')
                                    ->label(__('partner::partner.fields.expires_at')),
                                TextInput::make('position')
                                    ->label(__('partner::partner.fields.position'))
                                    ->helperText(__('partner::partner.fields.position_help'))
                                    ->numeric()
                                    ->minValue(0)
                                    ->default(0),
                            ]),
                        Tab::make(__('partner::partner.sections.contact'))
                            ->columns(2)
                            ->schema([
                                TextInput::make('contact_email')
                                    ->label(__('partner::partner.fields.contact_email'))
                                    ->email()
                                    ->maxLength(255),
                                TextInput::make('contact_phone')
                                    ->label(__('partner::partner.fields.contact_phone'))
                                    ->tel()
                                    ->maxLength(30),
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
