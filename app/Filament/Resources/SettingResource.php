<?php

namespace App\Filament\Resources;

use App\Filament\AbstractResource;
use App\Filament\NavigationGroup;
use App\Filament\Resources\SettingResource\Pages\EditSettings;
use App\Filament\Schemas\TranslatableTabs;
use App\Models\Setting;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Modules\Auth\Enums\PermissionEnum;
use Modules\Auth\Enums\RoleEnum;
use Modules\Auth\Models\User;
use Modules\Form\Support\Field;

/**
 * Réglages de l'application : une seule page, qui modifie l'unique ligne des
 * réglages (description traduite).
 */
class SettingResource extends AbstractResource
{
    protected static ?string $model = Setting::class;

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    protected static ?int $navigationSort = 100;

    protected static ?string $slug = 'application';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                // En onglets (décision 2026-11-23-application-en-onglets).
                Tabs::make('settings')
                    ->tabs([
                        Tab::make(__('settings.sections.general'))
                            ->schema([
                                // Traduite : un onglet par langue.
                                TranslatableTabs::make(fn (string $locale): array => [
                                    Textarea::make("description.{$locale}")
                                        ->label(__('settings.description'))
                                        ->helperText(__('settings.help.description'))
                                        ->maxLength(Field::TEXTAREA_MAX_LENGTH)
                                        ->rows(5),
                                ], 'description'),
                            ]),
                        // Décision 2026-11-19-contrat-paiement-visibilite-avis.
                        Tab::make(__('settings.sections.payment'))
                            ->schema([
                                Toggle::make('payment_required')
                                    ->label(__('settings.payment_required'))
                                    ->helperText(__('settings.help.payment_required')),
                                // Décision 2026-11-23-paiement-partenaires.
                                Toggle::make('partner_payment_required')
                                    ->label(__('settings.partner_payment_required'))
                                    ->helperText(__('settings.help.partner_payment_required')),
                            ]),
                        // Décision 2026-11-24-partenaire-compte-prevenu.
                        Tab::make(__('settings.sections.partners'))
                            ->schema([
                                Select::make('partner_notify_user_id')
                                    ->label(__('settings.partner_notify_user'))
                                    ->helperText(__('settings.help.partner_notify_user'))
                                    ->options(fn (): array => self::backOfficeUsers())
                                    ->searchable(),
                            ]),
                        // Décision 2026-11-19-age-minimum.
                        Tab::make(__('settings.sections.users'))
                            ->schema([
                                TextInput::make('minimum_age')
                                    ->label(__('settings.minimum_age'))
                                    ->helperText(__('settings.help.minimum_age'))
                                    ->integer()
                                    ->minValue(1)
                                    ->maxValue(120)
                                    ->suffix(__('settings.years')),
                            ]),
                        // Décision 2026-11-23-accueil-nombres-reglables.
                        Tab::make(__('settings.sections.home'))
                            ->columns(2)
                            ->schema([
                                TextInput::make('home_offers_per_city')
                                    ->label(__('settings.home_offers_per_city'))
                                    ->helperText(__('settings.help.home_count'))
                                    ->integer()
                                    ->minValue(1)
                                    ->maxValue(50)
                                    ->placeholder((string) Setting::HOME_DEFAULT_COUNT),
                                TextInput::make('home_partners')
                                    ->label(__('settings.home_partners'))
                                    ->helperText(__('settings.help.home_count'))
                                    ->integer()
                                    ->minValue(1)
                                    ->maxValue(50)
                                    ->placeholder((string) Setting::HOME_DEFAULT_COUNT),
                                // Décision 2026-11-25-bloc-a-la-une.
                                TextInput::make('home_featured')
                                    ->label(__('settings.home_featured'))
                                    ->helperText(__('settings.help.home_featured'))
                                    ->integer()
                                    ->minValue(Setting::HOME_FEATURED_MIN)
                                    ->maxValue(Setting::HOME_FEATURED_MAX)
                                    ->placeholder((string) Setting::HOME_FEATURED_DEFAULT),
                            ]),
                    ]),
            ]);
    }

    /**
     * Les comptes du back-office (super-administrateurs, accès au back-office) : « Prénom Nom (e-mail) », par nom.
     *
     * @return array<int, string>
     */
    private static function backOfficeUsers(): array
    {
        return User::role(RoleEnum::SUPER_ADMIN->value)->get()
            ->merge(User::permission(PermissionEnum::ACCESS_ADMIN->value)->get())
            ->unique(fn (User $user): int => (int) $user->getKey())
            ->sortBy(fn (User $user): string => (string) $user->full_name)
            ->mapWithKeys(fn (User $user): array => [(int) $user->getKey() => $user->full_name.' ('.$user->email.')'])
            ->all();
    }

    public static function getPages(): array
    {
        return [
            'index' => EditSettings::route('/'),
        ];
    }

    public static function getNavigationGroup(): ?string
    {
        return NavigationGroup::Settings->label();
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
