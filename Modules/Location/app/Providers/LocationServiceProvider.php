<?php

namespace Modules\Location\Providers;

use Illuminate\Support\Facades\Gate;
use Modules\Form\Support\FormRegistry;
use Modules\Form\Support\ListingRegistry;
use Modules\Location\Forms\AddressForm;
use Modules\Location\Lists\AddressListing;
use Modules\Location\Models\Address;
use Modules\Location\Models\Country;
use Modules\Location\Models\Region;
use Modules\Location\Policies\AddressPolicy;
use Modules\Location\Policies\CountryPolicy;
use Modules\Location\Policies\RegionPolicy;
use Nwidart\Modules\Support\ModuleServiceProvider;

class LocationServiceProvider extends ModuleServiceProvider
{
    public function boot(): void
    {
        parent::boot();

        Gate::policy(Country::class, CountryPolicy::class);
        Gate::policy(Region::class, RegionPolicy::class);
        Gate::policy(Address::class, AddressPolicy::class);

        // Formulaire et liste des adresses du compte, servis à l'app (module Form).
        $this->app->make(FormRegistry::class)->register(AddressForm::class);
        $this->app->make(ListingRegistry::class)->register(AddressListing::class);
    }

    /**
     * The name of the module.
     */
    protected string $name = 'Location';

    /**
     * The lowercase version of the module name.
     */
    protected string $nameLower = 'location';

    /**
     * Provider classes to register.
     *
     * @var string[]
     */
    protected array $providers = [
        EventServiceProvider::class,
        RouteServiceProvider::class,
    ];
}
