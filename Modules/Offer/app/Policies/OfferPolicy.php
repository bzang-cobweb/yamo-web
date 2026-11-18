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
use Modules\Offer\Models\Offer;

/**
 * Offres (décision 2026-11-18-offres-et-domaines). Back-office : permissions
 * `offers.*`. App : un compte gère ses offres du pays courant ; celle d'un autre
 * compte ou d'un autre pays répond 404 ;
 * en créer une demande le rôle `member`, si le domaine actif le permet.
 */
class OfferPolicy
{
    use HasBeforeAbility;

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

    public function delete(User $user, Offer $offer): bool|Response
    {
        if (Filament::isServing()) {
            return $user->hasPermissionTo(PermissionEnum::DELETE_OFFERS);
        }

        return $this->owns($user, $offer);
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
