<?php

namespace App\Providers;

use Dedoc\Scramble\Scramble;
use Dedoc\Scramble\Support\Generator\OpenApi;
use Dedoc\Scramble\Support\Generator\SecurityScheme;
use Illuminate\Support\ServiceProvider;

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
        // Contrat OpenAPI : l'API s'authentifie par jeton Sanctum (`Authorization: Bearer`).
        // Les routes publiques le déclarent par `@unauthenticated` sur leur action.
        if (class_exists(Scramble::class)) {
            Scramble::configure()->withDocumentTransformers(
                fn (OpenApi $openApi) => $openApi->secure(SecurityScheme::http('bearer')),
            );
        }
    }
}
