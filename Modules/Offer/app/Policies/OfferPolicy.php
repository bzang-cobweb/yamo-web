<?php

namespace Modules\Offer\Policies;

use Filament\Facades\Filament;
use Illuminate\Auth\Access\Response;
use Modules\Auth\Enums\PermissionEnum;
use Modules\Auth\Enums\RoleEnum;
use Modules\Auth\Models\User;
use Modules\Auth\Policies\Concerns\HasBeforeAbility;
use Modules\Location\Support\CurrentCountry;
use Modules\Offer\Domains\DomainRegistry;
use Modules\Offer\Enums\OfferStatusEnum;
use Modules\Offer\Models\Booking;
use Modules\Offer\Models\Offer;

/**
 * Offres (décision 2026-11-18-offres-et-domaines). Back-office : permissions
 * `offers.*`. App : un compte gère ses offres du pays courant ; celle d'un autre
 * compte ou d'un autre pays répond 404 ;
 * en créer une demande le rôle `member`, si le domaine actif le permet.
 */
class OfferPolicy
{
    use HasBeforeAbility { before as superAdminBefore; }

    /**
     * Le super administrateur a tous les droits, sauf sur les avis et les favoris :
     * leurs règles (offre publiée, pas la sienne) valent pour tout compte.
     */
    public function before(User $user, string $ability): ?bool
    {
        return in_array($ability, ['review', 'favorite', 'book'], true) ? null : $this->superAdminBefore($user, $ability);
    }

    public function viewAny(User $user): bool
    {
        if (Filament::isServing()) {
            return $user->hasPermissionTo(PermissionEnum::VIEW_OFFERS);
        }

        return true;
    }

    public function view(User $user, Offer $offer): bool|Response
    {
        if (Filament::isServing()) {
            return $user->hasPermissionTo(PermissionEnum::VIEW_OFFERS);
        }

        return $this->owns($user, $offer);
    }

    /**
     * La fiche d'une offre (décisions 2026-11-18-fiche-offre et
     * 2026-11-19-contrat-paiement-visibilite-avis), visiteur compris : son auteur la
     * voit toujours ; les autres, si elle leur est visible (`Offer::isVisibleToOthers()` :
     * publiée, du pays courant, pas expirée si le paiement est appliqué). Sinon 404.
     */
    public function show(?User $user, Offer $offer): Response
    {
        if ($user !== null && (int) $offer->user_id === (int) $user->getKey()) {
            return Response::allow();
        }

        return $offer->isVisibleToOthers() ? Response::allow() : Response::denyAsNotFound();
    }

    /**
     * Donner, modifier ou retirer son avis sur une offre (décision
     * 2026-11-19-contrat-avis-offres) : une offre visible des autres (sinon 404),
     * qui n'est pas la sienne (403).
     */
    public function review(User $user, Offer $offer): Response
    {
        if (! $offer->isVisibleToOthers()) {
            return Response::denyAsNotFound();
        }

        if ((int) $offer->user_id === (int) $user->getKey()) {
            return Response::deny(__('offer::review.own_offer'));
        }

        // Après un séjour ou une visite terminé dans ce logement ; un avis déjà donné reste modifiable
        // (décision 2026-11-22-avis-apres-demande-terminee).
        return $offer->reviews()->where('user_id', $user->getKey())->exists()
            || Booking::query()->where('offer_id', $offer->getKey())->where('user_id', $user->getKey())->completed()->exists()
            ? Response::allow()
            : Response::deny(__('offer::review.not_completed'));
    }

    /**
     * Demander une réservation ou une visite (décision 2026-11-19-contrat-reservations) :
     * une offre visible des autres que le domaine laisse demander (sinon 404), qui
     * n'est pas la sienne (403).
     */
    public function book(User $user, Offer $offer): Response
    {
        if (! $offer->isVisibleToOthers() || app(DomainRegistry::class)->require()->bookingType($offer) === null) {
            return Response::denyAsNotFound();
        }

        return (int) $offer->user_id === (int) $user->getKey()
            ? Response::deny(__('offer::booking.own_offer'))
            : Response::allow();
    }

    /**
     * Mettre une offre en favori, ou l'en retirer (décision 2026-11-19-contrat-vedette-favoris) :
     * mêmes règles qu'un avis — visible des autres (sinon 404), pas la sienne (403).
     */
    public function favorite(User $user, Offer $offer): Response
    {
        if (! $offer->isVisibleToOthers()) {
            return Response::denyAsNotFound();
        }

        return (int) $offer->user_id === (int) $user->getKey()
            ? Response::deny(__('offer::offer.favorites.own_offer'))
            : Response::allow();
    }

    public function create(User $user): bool
    {
        if (Filament::isServing()) {
            return $user->hasPermissionTo(PermissionEnum::CREATE_OFFERS);
        }

        CurrentCountry::require();

        return (bool) app(DomainRegistry::class)->active()?->membersCreateOffers() && $user->hasRole(RoleEnum::MEMBER);
    }

    public function update(User $user, Offer $offer): bool|Response
    {
        if (Filament::isServing()) {
            return $user->hasPermissionTo(PermissionEnum::UPDATE_OFFERS);
        }

        return $this->owns($user, $offer);
    }

    /** Publier son brouillon, depuis le menu de « Mes logements » (décision 2026-11-23-publier-depublier-menu). */
    public function publish(User $user, Offer $offer): Response
    {
        $owns = $this->owns($user, $offer);

        return $owns->denied() || $offer->status === OfferStatusEnum::DRAFT ? $owns : Response::deny();
    }

    /**
     * Repasser en brouillon son logement publié, sauf avec une demande en cours (décisions
     * 2026-11-22-logement-verrouille-par-demandes et 2026-11-23-publier-depublier-menu).
     */
    public function unpublish(User $user, Offer $offer): Response
    {
        $owns = $this->owns($user, $offer);
        if ($owns->denied()) {
            return $owns;
        }
        if ($offer->status !== OfferStatusEnum::PUBLISHED) {
            return Response::deny();
        }

        return $offer->hasOpenBookings() ? Response::deny(__('offer::offer.status_locked')) : Response::allow();
    }

    public function delete(User $user, Offer $offer): bool|Response
    {
        if (Filament::isServing()) {
            return $user->hasPermissionTo(PermissionEnum::DELETE_OFFERS);
        }

        $owns = $this->owns($user, $offer);
        if ($owns->denied()) {
            return $owns;
        }

        // Pas de retrait tant qu'il a une demande en cours (décision 2026-11-22-logement-verrouille-par-demandes).
        return $offer->hasOpenBookings() ? Response::deny(__('offer::offer.delete_locked')) : Response::allow();
    }

    public function deleteAny(User $user): bool
    {
        if (Filament::isServing()) {
            return $user->hasPermissionTo(PermissionEnum::DELETE_OFFERS);
        }

        return false;
    }

    public function restore(User $user, Offer $offer): bool
    {
        return Filament::isServing() && $user->hasPermissionTo(PermissionEnum::DELETE_OFFERS);
    }

    public function restoreAny(User $user): bool
    {
        return Filament::isServing() && $user->hasPermissionTo(PermissionEnum::DELETE_OFFERS);
    }

    public function forceDelete(User $user, Offer $offer): bool
    {
        return false;
    }

    public function forceDeleteAny(User $user): bool
    {
        return false;
    }

    /**
     * La sienne, et dans le pays courant (décision 2026-11-18-espace-pays) ; sinon 404.
     */
    private function owns(User $user, Offer $offer): Response
    {
        return (int) $offer->user_id === (int) $user->getKey() && (int) $offer->country_id === (int) CurrentCountry::require()->getKey()
            ? Response::allow()
            : Response::denyAsNotFound();
    }
}
