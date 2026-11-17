<?php

namespace Modules\Form\Providers;

use Illuminate\Auth\Access\Response;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Modules\Form\Support\Form;
use Modules\Form\Support\FormRegistry;
use Modules\Form\Support\Listing;
use Modules\Form\Support\ListingRegistry;
use Nwidart\Modules\Support\ModuleServiceProvider;

class FormServiceProvider extends ModuleServiceProvider
{
    public function register(): void
    {
        parent::register();

        // Un seul registre : chaque module y inscrit ses formulaires dans son boot().
        $this->app->singleton(FormRegistry::class);
        // De même pour les listes (décision 2026-11-18-adresses-liste-generique).
        $this->app->singleton(ListingRegistry::class);
    }

    public function boot(): void
    {
        parent::boot();

        // `{form}` : le formulaire du registre, construit pour le compte du jeton
        // s'il y en a un (valeurs actuelles) ; inconnu → 404.
        Route::bind('form', fn (string $name): Form => $this->app->make(FormRegistry::class)
            ->make($name, request()->user('sanctum')) ?? abort(404));

        Gate::define('viewForm', fn (?Authenticatable $user, Form $form): Response => match (true) {
            $form->canBeViewedBy($user) => Response::allow(),
            $user === null => Response::denyWithStatus(401),
            default => Response::deny(),
        });

        // `{list}` : la liste du registre, construite pour le compte du jeton ;
        // inconnue → 404.
        Route::bind('list', fn (string $name): Listing => $this->app->make(ListingRegistry::class)
            ->make($name, request()->user('sanctum')) ?? abort(404));

        Gate::define('viewList', fn (?Authenticatable $user, Listing $list): Response => match (true) {
            $list->canBeViewedBy($user) => Response::allow(),
            $user === null => Response::denyWithStatus(401),
            default => Response::deny(),
        });
    }

    /**
     * The name of the module.
     */
    protected string $name = 'Form';

    /**
     * The lowercase version of the module name.
     */
    protected string $nameLower = 'form';

    /**
     * Command classes to register.
     *
     * @var string[]
     */
    // protected array $commands = [];

    /**
     * Provider classes to register.
     *
     * @var string[]
     */
    protected array $providers = [
        EventServiceProvider::class,
        RouteServiceProvider::class,
    ];

    /**
     * Define module schedules.
     *
     * @param  $schedule
     */
    // protected function configureSchedules(Schedule $schedule): void
    // {
    //     $schedule->command('inspire')->hourly();
    // }
}
