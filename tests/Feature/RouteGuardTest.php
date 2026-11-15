<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * Balayage de la table des routes : toute route de contrôleur de l'application
 * ou d'un module porte une garde `can:` (policy ou capacité Gate), sauf celles
 * listées ici avec leur motif (bza-laravel:create-policy).
 */
class RouteGuardTest extends TestCase
{
    /** @var array<string, string> URI => motif */
    private const WITHOUT_CAN = [
        'api/v1/auth/login' => 'publique : connexion, limitée par throttle:auth-login',
        'api/v1/auth/logout' => 'agit sur le jeton de la requête, pas sur un modèle : gardée par auth:sanctum',
    ];

    public function test_every_controller_route_is_guarded(): void
    {
        $unguarded = [];

        foreach (Route::getRoutes()->getRoutes() as $route) {
            $action = $route->getActionName();

            if (! str_starts_with($action, 'App\\Http\\Controllers\\') && ! preg_match('/^Modules\\\\\w+\\\\Http\\\\Controllers\\\\/', $action)) {
                continue;
            }

            $guarded = collect($route->gatherMiddleware())
                ->contains(fn ($middleware) => is_string($middleware) && str_starts_with($middleware, 'can:'));

            if (! $guarded && ! array_key_exists($route->uri(), self::WITHOUT_CAN)) {
                $unguarded[] = $route->uri();
            }
        }

        $this->assertSame([], $unguarded, 'Routes sans garde can: : '.implode(', ', $unguarded));
    }

    public function test_the_exception_list_only_names_existing_routes(): void
    {
        $uris = collect(Route::getRoutes()->getRoutes())->map->uri()->all();

        foreach (array_keys(self::WITHOUT_CAN) as $uri) {
            $this->assertContains($uri, $uris, "Exception obsolète : {$uri}");
        }
    }
}
