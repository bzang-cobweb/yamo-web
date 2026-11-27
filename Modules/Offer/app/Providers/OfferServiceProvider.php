<?php

namespace Modules\Offer\Providers;

use Bza\Form\Support\FormRegistry;
use Bza\Form\Support\ListingRegistry;
use Illuminate\Support\Facades\Gate;
use Modules\Auth\Models\User;
use Modules\Auth\Support\ProfileMenuLinks;
use Modules\Notification\Support\NotificationFilters;
use Modules\Offer\Console\CompleteBookingsCommand;
use Modules\Offer\Console\ExpireBookingsCommand;
use Modules\Offer\Console\InviteReviewsCommand;
use Modules\Offer\Domains\DomainRegistry;
use Modules\Offer\Domains\RentalDomain;
use Modules\Offer\Forms\BookingAcceptForm;
use Modules\Offer\Forms\BookingAnswerForm;
use Modules\Offer\Forms\BookingCancelForm;
use Modules\Offer\Forms\BookingEditForm;
use Modules\Offer\Forms\BookingInviteReviewForm;
use Modules\Offer\Forms\BookingRefuseForm;
use Modules\Offer\Forms\OfferBookingForm;
use Modules\Offer\Forms\OfferForm;
use Modules\Offer\Forms\OfferMessageForm;
use Modules\Offer\Forms\OfferReviewForm;
use Modules\Offer\Forms\WishlistForm;
use Modules\Offer\Lists\BookingListing;
use Modules\Offer\Lists\OfferBrowseListing;
use Modules\Offer\Lists\OfferListing;
use Modules\Offer\Lists\OfferReviewListing;
use Modules\Offer\Lists\ReceivedBookingListing;
use Modules\Offer\Lists\WishlistListing;
use Modules\Offer\Lists\WishlistOffersListing;
use Modules\Offer\Models\Booking;
use Modules\Offer\Models\Offer;
use Modules\Offer\Models\OfferCategory;
use Modules\Offer\Models\OfferReview;
use Modules\Offer\Models\Wishlist;
use Modules\Offer\Notifications\OfferNotificationFilter;
use Modules\Offer\Policies\BookingPolicy;
use Modules\Offer\Policies\OfferCategoryPolicy;
use Modules\Offer\Policies\OfferPolicy;
use Modules\Offer\Policies\OfferReviewPolicy;
use Modules\Offer\Policies\WishlistPolicy;
use Modules\Offer\Support\BookingCache;
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
    public array $singletons = [DomainRegistry::class => DomainRegistry::class, BookingCache::class => BookingCache::class];

    /** @var list<class-string> */
    protected array $commands = [CompleteBookingsCommand::class, ExpireBookingsCommand::class, InviteReviewsCommand::class];

    public function boot(): void
    {
        parent::boot();

        // Filtrer ses notifications par logement et statut du logement (décision 2026-11-20-filtres-notifications).
        $this->app->make(NotificationFilters::class)->register(OfferNotificationFilter::class);

        $domains = $this->app->make(DomainRegistry::class);
        $domains->register(RentalDomain::class);
        // Un domaine inconnu est une erreur claire, dès le démarrage.
        $domains->active();

        Gate::policy(Offer::class, OfferPolicy::class);
        Gate::policy(OfferCategory::class, OfferCategoryPolicy::class);
        Gate::policy(OfferReview::class, OfferReviewPolicy::class);
        Gate::policy(Booking::class, BookingPolicy::class);
        Gate::policy(Wishlist::class, WishlistPolicy::class);
        // La page d'un membre : tout compte connecté (décision 2026-11-19-page-membre) ; la règle a son domicile
        // ici, la route porte sa garde (décision 2026-11-24-partenaires-policy-routes).
        Gate::define('viewMember', fn (User $viewer, User $member): bool => true);

        // Formulaire et liste servis à l'app : indisponibles (404) sans domaine choisi.
        $this->app->make(FormRegistry::class)->register(OfferForm::class);
        // Son avis sur une offre, ouvert depuis la fiche (décision 2026-11-19-contrat-avis-offres).
        $this->app->make(FormRegistry::class)->register(OfferReviewForm::class);
        // Écrire à l'éditeur d'une offre, ou lui répondre (décision 2026-11-26-messages-editeur).
        $this->app->make(FormRegistry::class)->register(OfferMessageForm::class);
        $this->app->make(ListingRegistry::class)->register(OfferListing::class);
        // La vitrine de l'accueil, ouverte sans connexion (décision 2026-11-18-accueil-offres-du-pays).
        $this->app->make(ListingRegistry::class)->register(OfferBrowseListing::class);
        // « Mes favoris » : ses listes de favoris, les offres d'une liste, et renommer une liste (décision
        // 2026-11-27-listes-de-favoris).
        $this->app->make(ListingRegistry::class)->register(WishlistListing::class);
        $this->app->make(ListingRegistry::class)->register(WishlistOffersListing::class);
        $this->app->make(FormRegistry::class)->register(WishlistForm::class);
        // Réservations et visites (décision 2026-11-19-contrat-reservations) : la demande depuis
        // la fiche, la réponse du propriétaire, et les deux listes du profil.
        $this->app->make(FormRegistry::class)->register(OfferBookingForm::class);
        // Modifier sa demande en attente (décision 2026-11-21-modifier-reservation).
        $this->app->make(FormRegistry::class)->register(BookingEditForm::class);
        $this->app->make(FormRegistry::class)->register(BookingCancelForm::class);
        $this->app->make(FormRegistry::class)->register(BookingAnswerForm::class);
        $this->app->make(FormRegistry::class)->register(BookingRefuseForm::class);
        $this->app->make(FormRegistry::class)->register(BookingAcceptForm::class);
        $this->app->make(FormRegistry::class)->register(BookingInviteReviewForm::class);
        $this->app->make(ListingRegistry::class)->register(BookingListing::class);
        $this->app->make(ListingRegistry::class)->register(ReceivedBookingListing::class);
        // Tous les avis d'une offre, avec la recherche (décision 2026-11-25-liste-avis-generique).
        $this->app->make(ListingRegistry::class)->register(OfferReviewListing::class);

        // Le menu du profil en groupes (décision 2026-11-19-contrat-reservations-logements-profil) :
        // « Voyageur » (ses réservations, ses favoris), puis « Hôte » (ses logements). Les demandes
        // reçues ne sont plus un lien du menu : elles s'ouvrent depuis chaque logement.
        $links = $this->app->make(ProfileMenuLinks::class);
        $links->addGroup('traveler', fn (): string => __('offer::offer.profile_groups.traveler'), 20);
        $links->addGroup('host', fn (): string => __('offer::offer.profile_groups.host'), 30);
        $links->addList(
            'bookings',
            fn (): string => __('offer::booking.list.mine'),
            fn (): ?string => BookingListing::isAvailable() ? BookingListing::name() : null,
            group: 'traveler',
        );
        $links->addList(
            'favorites',
            fn (): string => __('offer::offer.favorites.title'),
            fn (): ?string => WishlistListing::isAvailable() ? WishlistListing::name() : null,
            group: 'traveler',
        );
        // « Mes logements » (libellé du domaine).
        $links->addList(
            'offers',
            fn (): string => $domains->require()->mineLabel(),
            fn (): ?string => OfferListing::isAvailable() ? OfferListing::name() : null,
            group: 'host',
        );
    }
}
