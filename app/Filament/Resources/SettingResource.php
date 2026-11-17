<?php

namespace App\Filament\Resources;

use App\Filament\AbstractResource;
use App\Filament\Resources\SettingResource\Pages\EditSettings;
use App\Models\Setting;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use LaraZeus\SpatieTranslatable\Resources\Concerns\Translatable;
use Modules\Form\Support\Field;

/**
 * Réglages de l'application : une seule page, qui modifie l'unique ligne des
 * réglages (logo, description traduite).
 */
class SettingResource extends AbstractResource
{
    use Translatable;

    protected static ?string $model = Setting::class;

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    protected static ?int $navigationSort = 100;

    protected static ?string $slug = 'application';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make(__('settings.sections.general'))
                    ->schema([
                        SpatieMediaLibraryFileUpload::make('logo')
                            ->label(__('settings.logo'))
                            ->helperText(__('settings.help.logo'))
                            ->collection(Setting::LOGO)
                            ->image()
                            ->maxSize(2048),
                        // Traduite : le sélecteur de langue de la page choisit fr ou en.
                        Textarea::make('description')
                            ->label(__('settings.description'))
                            ->helperText(__('settings.help.description'))
                            ->maxLength(Field::TEXTAREA_MAX_LENGTH)
                            ->rows(5),
                    ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => EditSettings::route('/'),
        ];
    }

    public static function getNavigationGroup(): ?string
    {
        return __('settings.navigation_group');
    }

    public static function getNavigationLabel(): string
    {
        return __('settings.title');
    }

    public static function getModelLabel(): string
    {
        return __('settings.title');
    }

    public static function getPluralModelLabel(): string
    {
        return __('settings.title');
    }
}
