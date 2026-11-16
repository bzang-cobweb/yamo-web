<?php

use App\Exceptions\TranslatedApiErrors;
use App\Http\Middleware\SetLocale;
use App\Http\Middleware\UseSanctumGuard;
use Illuminate\Contracts\Auth\Middleware\AuthenticatesRequests;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->api(append: [UseSanctumGuard::class, SetLocale::class]);
        // La langue est fixée avant l'authentification : un refus (401) est rendu
        // dans la langue de la requête. SetLocale lit lui-même le jeton (garde sanctum).
        $middleware->prependToPriorityList(before: AuthenticatesRequests::class, prepend: SetLocale::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
        TranslatedApiErrors::register($exceptions);
    })->create();
