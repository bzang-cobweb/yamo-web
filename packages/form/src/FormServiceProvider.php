<?php

namespace Bza\Form;

use Bza\Form\Support\Form;
use Bza\Form\Support\FormRegistry;
use Bza\Form\Support\Listing;
use Bza\Form\Support\ListingRegistry;
use Illuminate\Auth\Access\Response;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

/**
 * Wires the package into the application (decision 2026-11-27-paquet-form): the form and list registries, the
 * `{form}` and `{list}` route bindings, the `viewForm` and `viewList` gates, the API routes, the `form::`
 * translations, the `form` configuration and the upload migration.
 */
class FormServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/form.php', 'form');

        // A single registry: each module registers its forms in its own boot().
        $this->app->singleton(FormRegistry::class);
        // The same for lists (decision 2026-11-18-adresses-liste-generique).
        $this->app->singleton(ListingRegistry::class);
    }

    public function boot(): void
    {
        $this->loadTranslationsFrom(__DIR__.'/../lang', 'form');
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        $this->publishes([__DIR__.'/../config/form.php' => config_path('form.php')], 'form-config');

        // `{form}`: the registry's form, built for the token's account when there is one (current values);
        // unknown → 404.
        Route::bind('form', fn (string $name): Form => $this->app->make(FormRegistry::class)
            ->make($name, request()->user('sanctum')) ?? abort(404));

        Gate::define('viewForm', fn (?Authenticatable $user, Form $form): Response => match (true) {
            $form->canBeViewedBy($user) => Response::allow(),
            $user === null => Response::denyWithStatus(401),
            default => Response::deny(),
        });

        // `{list}`: the registry's list, built for the token's account; unknown → 404.
        Route::bind('list', fn (string $name): Listing => $this->app->make(ListingRegistry::class)
            ->make($name, request()->user('sanctum')) ?? abort(404));

        Gate::define('viewList', fn (?Authenticatable $user, Listing $list): Response => match (true) {
            $list->canBeViewedBy($user) => Response::allow(),
            $user === null => Response::denyWithStatus(401),
            default => Response::deny(),
        });

        // The API routes: `api` prefix and `api.` names, unchanged for the app.
        Route::middleware('api')->prefix('api')->name('api.')->group(__DIR__.'/../routes/api.php');
    }
}
