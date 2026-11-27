<?php

namespace App\Providers\Filament;

use App\Filament\NavigationGroup;
use App\Support\Brand;
use Bza\ConfigViewer\Filament\ConfigViewerPlugin;
use Bza\MigrationViewer\Filament\MigrationViewerPlugin;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets\AccountWidget;
use Filament\Widgets\FilamentInfoWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Nwidart\Modules\Facades\Module;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        // L'identifiant d'abord : la découverte des composants en dépend.
        $panel
            ->default()
            ->id('admin')
            ->path('admin');

        // Chaque module actif apporte ses ressources, pages et widgets Filament.
        foreach (Module::allEnabled() as $module) {
            $namespace = 'Modules\\'.$module->getName().'\\Filament';
            $panel
                ->discoverResources(in: $module->getExtraPath('app/Filament/Resources'), for: $namespace.'\\Resources')
                ->discoverPages(in: $module->getExtraPath('app/Filament/Pages'), for: $namespace.'\\Pages')
                ->discoverWidgets(in: $module->getExtraPath('app/Filament/Widgets'), for: $namespace.'\\Widgets');
        }

        return $panel
            ->login()
            // Couleurs du logo YaMo.
            ->colors([
                'primary' => Color::hex(Brand::PRIMARY),
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->widgets([
                AccountWidget::class,
                FilamentInfoWidget::class,
            ])
            // La configuration effective, modules compris, en lecture seule ; réservée aux super administrateurs.
            ->plugin(ConfigViewerPlugin::make()
                ->navigationGroup(fn (): string => NavigationGroup::Settings->label())
                ->navigationSort(110)
                ->authorize(fn (): bool => (bool) auth()->user()?->isSuperAdmin()))
            // Menu « Modèles de données » : les fichiers de migration regroupés par table, modules compris,
            // puis les tables enregistrées ; réservés aux super administrateurs.
            ->plugin(MigrationViewerPlugin::make()
                ->navigationGroup(fn (): string => NavigationGroup::DataModels->label())
                ->navigationSort(10)
                ->authorize(fn (): bool => (bool) auth()->user()?->isSuperAdmin()))
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
