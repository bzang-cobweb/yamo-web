<?php

namespace Modules\Partner\Providers;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\Gate;
use Modules\Auth\Models\User;
use Modules\Auth\Support\ProfileMenuLinks;
use Modules\Form\Support\FormRegistry;
use Modules\Form\Support\ListingRegistry;
use Modules\Notification\Support\NotificationFilters;
use Modules\Partner\Forms\PartnerForm;
use Modules\Partner\Lists\MyPartnerListing;
use Modules\Partner\Lists\PartnerListing;
use Modules\Partner\Models\Partner;
use Modules\Partner\Notifications\PartnerNotificationFilter;
use Modules\Partner\Policies\PartnerPolicy;
use Nwidart\Modules\Support\ModuleServiceProvider;

class PartnerServiceProvider extends ModuleServiceProvider
{
    public function boot(): void
    {
        parent::boot();

        Gate::policy(Partner::class, PartnerPolicy::class);

        // Ses notifications dans « Notifications » : le type « Partenaire », par statut (décision
        // 2026-11-24-partenaires-notifications-filtre).
        $this->app->make(NotificationFilters::class)->register(PartnerNotificationFilter::class);

        // Les partenaires d'un compte, déclarés ici pour que le module Auth ignore les partenaires (décision
        // 2026-11-24-partenaires-par-les-membres) ; le back-office s'en sert pour rattacher des comptes.
        User::resolveRelationUsing('partners', fn (User $user): BelongsToMany => $user->belongsToMany(Partner::class)->withPivot('is_admin')->withTimestamps());

        // La page « Partenaires » de l'app, une liste comme les autres (décision 2026-11-23-partenaires-liste-detail).
        $this->app->make(ListingRegistry::class)->register(PartnerListing::class);

        // « Mes partenaires » : un membre propose et gère ses partenaires (décision 2026-11-24-partenaires-par-les-membres).
        $this->app->make(FormRegistry::class)->register(PartnerForm::class);
        $this->app->make(ListingRegistry::class)->register(MyPartnerListing::class);
        $links = $this->app->make(ProfileMenuLinks::class);
        $links->addGroup('partner', fn (): string => __('partner::partner.member.group'), 40);
        $links->addList('partners', fn (): string => __('partner::partner.member.mine'), fn (): string => MyPartnerListing::name(), group: 'partner');
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
