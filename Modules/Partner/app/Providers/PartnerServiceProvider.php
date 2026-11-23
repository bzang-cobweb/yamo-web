<?php

namespace Modules\Partner\Providers;

use Illuminate\Support\Facades\Gate;
use Modules\Form\Support\ListingRegistry;
use Modules\Partner\Lists\PartnerListing;
use Modules\Partner\Models\Partner;
use Modules\Partner\Policies\PartnerPolicy;
use Nwidart\Modules\Support\ModuleServiceProvider;

class PartnerServiceProvider extends ModuleServiceProvider
{
    public function boot(): void
    {
        parent::boot();

        Gate::policy(Partner::class, PartnerPolicy::class);

        // La page « Partenaires » de l'app, une liste comme les autres (décision 2026-11-23-partenaires-liste-detail).
        $this->app->make(ListingRegistry::class)->register(PartnerListing::class);
    }

    /**
     * The name of the module.
     */
    protected string $name = 'Partner';

    /**
     * The lowercase version of the module name.
     */
    protected string $nameLower = 'partner';

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
}
