<?php

namespace Modules\Location\Providers;

use Illuminate\Support\Facades\Gate;
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
