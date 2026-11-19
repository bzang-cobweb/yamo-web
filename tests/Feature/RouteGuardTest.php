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
        'api/v1/auth/register' => 'publique : inscription, limitée par throttle:auth-register ; le compte naît après les codes',
        'api/v1/auth/password/forgot' => 'publique : mot de passe oublié, limitée par throttle:auth-login ; ne révèle pas si un compte existe',
        'api/v1/auth/account/restore' => 'publique : récupérer son compte en attente de suppression, limitée par throttle:auth-codes ; exige un défi de connexion dont le code a été validé',
        'api/v1/auth/password/reset' => 'publique : nouveau mot de passe, limitée par throttle:auth-codes ; exige un défi dont le code a été validé',
        'api/v1/auth/codes/verify' => 'publique : validation de code, limitée par throttle:auth-codes ; un défi de changement exige son compte (service)',
        'api/v1/auth/codes/resend' => 'publique : renvoi de code, limité par throttle:auth-codes ; un défi de changement exige son compte (service)',
        'api/v1/auth/logout' => 'agit sur le jeton de la requête, pas sur un modèle : gardée par auth:sanctum',
        'api/v1/app' => "publique : nom et description de l'application (page d'accueil)",
        'api/v1/locales' => 'publique : langues disponibles, aucune donnée de compte',
        'api/v1/messages' => "publique : textes de l'interface de l'app, aucune donnée de compte",
        'api/v1/offers/{offer}' => "publique : fiche d'une offre ; policy `show` appelée dans le contrôleur avec le compte facultatif (Sanctum)",
        'api/v1/countries' => "publique : pays actifs proposés à l'entrée de l'app, aucune donnée de compte",
        // Module Notification (décision 2026-11-19-contrat-centre-notifications) : ses propres notifications.
        'api/v1/notifications' => 'ses notifications seulement (relation `notifications` du compte) : gardée par auth:sanctum',
        'api/v1/notifications/unread-count' => 'le nombre de ses notifications non lues : gardée par auth:sanctum',
        'api/v1/notifications/read' => 'marque lues ses seules notifications : gardée par auth:sanctum',
        'api/v1/notifications/{notification}/read' => "cherchée parmi ses seules notifications (404 sinon) : gardée par auth:sanctum",
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
