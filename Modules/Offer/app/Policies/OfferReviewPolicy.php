<?php

namespace Modules\Offer\Policies;

use Filament\Facades\Filament;
use Modules\Auth\Enums\PermissionEnum;
use Modules\Auth\Models\User;
use Modules\Auth\Policies\Concerns\HasBeforeAbility;
use Modules\Offer\Models\OfferReview;

/**
 * Modération des avis dans le back-office (décision 2026-11-19-contrat-avis-offres) :
 * voir (`offer_reviews.view`) et supprimer (`offer_reviews.delete`), jamais créer
 * ni modifier. Dans l'app, un membre gère son avis par la règle `review` de
 * `OfferPolicy`.
 */
class OfferReviewPolicy
{
    use HasBeforeAbility { before as superAdminBefore; }

    /**
     * Le super administrateur a tous les droits, sauf créer ou modifier un avis :
     * un avis n'appartient qu'à son auteur.
     */
    public function before(User $user, string $ability): ?bool
    {
        return in_array($ability, ['create', 'update'], true) ? null : $this->superAdminBefore($user, $ability);
    }

    public function viewAny(User $user): bool
    {
        return Filament::isServing() && $user->hasPermissionTo(PermissionEnum::VIEW_OFFER_REVIEWS);
    }

    public function view(User $user, OfferReview $review): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, OfferReview $review): bool
    {
        return false;
    }

    public function delete(User $user, OfferReview $review): bool
    {
        return Filament::isServing() && $user->hasPermissionTo(PermissionEnum::DELETE_OFFER_REVIEWS);
    }

    public function deleteAny(User $user): bool
    {
        return Filament::isServing() && $user->hasPermissionTo(PermissionEnum::DELETE_OFFER_REVIEWS);
    }
}
