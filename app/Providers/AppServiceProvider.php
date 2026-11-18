<?php

namespace App\Providers;

use App\Models\Setting;
use App\Policies\SettingPolicy;
use App\Support\Locales;
use Dedoc\Scramble\Scramble;
use Dedoc\Scramble\Support\Generator\OpenApi;
use Dedoc\Scramble\Support\Generator\SecurityScheme;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Spatie\Translatable\Translatable;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::policy(Setting::class, SettingPolicy::class);

        // Texte traduit absent : la langue par défaut, puis n'importe quelle langue
        // disponible, jamais un vide quand un texte existe (décision
        // 2026-11-18-offres-multilingues-categories-arbre).
        app(Translatable::class)->fallback(fallbackLocale: Locales::default(), fallbackAny: true);

        // Contrat OpenAPI : l'API s'authentifie par jeton Sanctum (`Authorization: Bearer`).
        // Les routes publiques le déclarent par `@unauthenticated` sur leur action.
        if (class_exists(Scramble::class)) {
            Scramble::configure()->withDocumentTransformers(
                fn (OpenApi $openApi) => $openApi->secure(SecurityScheme::http('bearer')),
            );
        }
    }
}
