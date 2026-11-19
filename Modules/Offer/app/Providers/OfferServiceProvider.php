<?php

namespace Modules\Offer\Providers;

use Illuminate\Support\Facades\Gate;
use Modules\Auth\Models\User;
use Modules\Auth\Support\ProfileMenuLinks;
use Modules\Form\Support\FormRegistry;
use Modules\Form\Support\ListingRegistry;
use Modules\Location\Support\CurrentCountry;
use Modules\Offer\Domains\DomainRegistry;
use Modules\Offer\Domains\RentalDomain;
use Modules\Offer\Forms\BookingAnswerForm;
use Modules\Offer\Forms\OfferBookingForm;
use Modules\Offer\Forms\OfferForm;
use Modules\Offer\Forms\OfferReviewForm;
use Modules\Offer\Lists\BookingListing;
use Modules\Offer\Lists\OfferBrowseListing;
use Modules\Offer\Lists\OfferFavoritesListing;
use Modules\Offer\Lists\OfferListing;
use Modules\Offer\Lists\ReceivedBookingListing;
use Modules\Offer\Models\Booking;
use Modules\Offer\Models\Offer;
use Modules\Offer\Models\OfferCategory;
use Modules\Offer\Models\OfferReview;
use Modules\Offer\Policies\BookingPolicy;
use Modules\Offer\Policies\OfferCategoryPolicy;
use Modules\Offer\Policies\OfferPolicy;
use Modules\Offer\Policies\OfferReviewPolicy;
use Nwidart\Modules\Support\ModuleServiceProvider;

/**
 * Module Offer (décision 2026-11-18-offres-et-domaines) : domaines connus, et ce
 * que le domaine actif expose à l'app (formulaire, liste, lien du menu). Un
 * domaine inconnu dans `config('offer.domain')` arrête le démarrage.
 */
class OfferServiceProvider extends ModuleServiceProvider
{
    /**
     * The name of the module.
     */
    protected string $name = 'Offer';

    /**
     * The lowercase version of the module name.
     */
    protected string $nameLower = 'offer';

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
     * @var array<class-string, class-string>
     */
    public array $singletons = [DomainRegistry::class => DomainRegistry::class];

    public function boot(): void
    {
        parent::boot();

        $domains = $this->app->make(DomainRegistry::class);
        $domains->register(RentalDomain::class);
        // Un domaine inconnu est une erreur claire, dès le démarrage.
        $domains->active();

        Gate::policy(Offer::class, OfferPolicy::class);
        Gate::policy(OfferCategory::class, OfferCategoryPolicy::class);
        Gate::policy(OfferReview::class, OfferReviewPolicy::class);
        Gate::policy(Booking::class, BookingPolicy::class);

        // Formulaire et liste servis à l'app : indisponibles (404) sans domaine choisi.
        $this->app->make(FormRegistry::class)->register(OfferForm::class);
        // Son avis sur une offre, ouvert depuis la fiche (décision 2026-11-19-contrat-avis-offres).
        $this->app->make(FormRegistry::class)->register(OfferReviewForm::class);
        $this->app->make(ListingRegistry::class)->register(OfferListing::class);
        // La vitrine de l'accueil, ouverte sans connexion (décision 2026-11-18-accueil-offres-du-pays).
        $this->app->make(ListingRegistry::class)->register(OfferBrowseListing::class);
        // « Mes favoris » (décision 2026-11-19-contrat-vedette-favoris).
        $this->app->make(ListingRegistry::class)->register(OfferFavoritesListing::class);
        // Réservations et visites (décision 2026-11-19-contrat-reservations) : la demande depuis
        // la fiche, la réponse du propriétaire, et les deux listes du profil.
        $this->app->make(FormRegistry::class)->register(OfferBookingForm::class);
        $this->app->make(FormRegistry::class)->register(BookingAnswerForm::class);
        $this->app->make(ListingRegistry::class)->register(BookingListing::class);
        $this->app->make(ListingRegistry::class)->register(ReceivedBookingListing::class);

        // « Mes logements » (libellé du domaine), après les liens d'Auth.
        $this->app->make(ProfileMenuLinks::class)->addList(
            'offers',
            fn (): string => $domains->require()->mineLabel(),
            fn (): ?string => OfferListing::isAvailable() ? OfferListing::name() : null,
        );
        $this->app->make(ProfileMenuLinks::class)->addList(
            'favorites',
            fn (): string => __('offer::offer.favorites.title'),
            fn (): ?string => OfferFavoritesListing::isAvailable() ? OfferFavoritesListing::name() : null,
        );
        $this->app->make(ProfileMenuLinks::class)->addList(
            'bookings',
            fn (): string => __('offer::booking.list.mine'),
            fn (): ?string => BookingListing::isAvailable() ? BookingListing::name() : null,
        );
        // Pour qui a des offres dans le pays courant.
        $this->app->make(ProfileMenuLinks::class)->addList(
            'bookings_received',
            fn (): string => __('offer::booking.list.received'),
            fn (): ?string => ReceivedBookingListing::isAvailable() ? ReceivedBookingListing::name() : null,
            fn (User $user): bool => Offer::query()->withTrashed()->where('user_id', $user->getKey())
                ->where('country_id', CurrentCountry::require()->getKey())->exists(),
        );
    }
}
