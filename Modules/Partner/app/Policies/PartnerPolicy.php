<?php

namespace Modules\Partner\Policies;

use Filament\Facades\Filament;
use Illuminate\Auth\Access\Response;
use Modules\Auth\Enums\PermissionEnum;
use Modules\Auth\Models\User;
use Modules\Auth\Policies\Concerns\HasBeforeAbility;
use Modules\Location\Support\CurrentCountry;
use Modules\Partner\Models\Partner;

/**
 * Au back-office, selon les permissions `partners.*` (décision 2026-11-23-module-partenaires). Dans l'app (décisions
 * 2026-11-24-partenaires-par-les-membres et 2026-11-24-partenaires-policy-routes) : tout le monde, visiteur compris,
 * voit les partenaires visibles ; un compte rattaché voit aussi les siens quel que soit leur statut et les modifie ;
 * un administrateur (`is_admin`) seul les retire ; pour les autres, un partenaire qu'on ne peut pas voir n'existe
 * pas (404). Toutes les routes de l'API passent par ici.
 */
class PartnerPolicy
{
    use HasBeforeAbility { before as superAdminBefore; }

    /**
     * Le super administrateur a tous les droits au back-office ; dans l'app, voir, modifier et retirer suivent la
     * règle de tout compte (être rattaché, administrateur pour retirer).
     */
    public function before(User $user, string $ability): ?bool
    {
        if (! Filament::isServing() && in_array($ability, ['view', 'update', 'delete'], true)) {
            return null;
        }

        return $this->superAdminBefore($user, $ability);
    }

    /** Au back-office, `partners.view` ; dans l'app, le carrousel et la liste sont publics. */
    public function viewAny(?User $user): bool
    {
        return Filament::isServing() ? (bool) $user?->hasPermissionTo(PermissionEnum::VIEW_PARTNERS) : true;
    }

    /**
     * Au back-office, `partners.view`. Dans l'app, visiteur compris : un partenaire visible (publié, du pays courant,
     * campagne en cours), ou, quel que soit son statut, pour un compte rattaché ; sinon 404.
     */
    public function view(?User $user, Partner $partner): bool|Response
    {
        if (Filament::isServing()) {
            return (bool) $user?->hasPermissionTo(PermissionEnum::VIEW_PARTNERS);
        }

        if ($partner->isVisible()) {
            return Response::allow();
        }
        if ($user === null) {
            return Response::denyAsNotFound();
        }

        return $this->attached($user, $partner);
    }

    /** Au back-office, `partners.create` ; dans l'app, tout compte connecté propose un partenaire. */
    public function create(User $user): bool
    {
        return Filament::isServing() ? $user->hasPermissionTo(PermissionEnum::CREATE_PARTNERS) : true;
    }

    /** Au back-office, `partners.update` ; dans l'app, un compte rattaché (404 sinon). */
    public function update(User $user, Partner $partner): bool|Response
    {
        return Filament::isServing() ? $user->hasPermissionTo(PermissionEnum::UPDATE_PARTNERS) : $this->attached($user, $partner);
    }

    /** Au back-office, `partners.delete` ; dans l'app, un administrateur (`is_admin`) : 404 pour un étranger, 403 pour un rattaché. */
    public function delete(User $user, Partner $partner): bool|Response
    {
        if (Filament::isServing()) {
            return $user->hasPermissionTo(PermissionEnum::DELETE_PARTNERS);
        }

        $attached = $this->attached($user, $partner);
        if ($attached->denied()) {
            return $attached;
        }
        if (! $partner->hasAdmin($user->getKey())) {
            return Response::deny();
        }

        return Response::allow();
    }

    public function deleteAny(User $user): bool
    {
        return Filament::isServing() && $user->hasPermissionTo(PermissionEnum::DELETE_PARTNERS);
    }

    /** Rattaché au partenaire (`partner_user`), dans le pays courant ; sinon 404, comme un partenaire qui n'existe pas. */
    /**
     * Écrire dans le fil d'un partenaire (décision 2026-11-27-partenaire-messages) : un compte rattaché (404 sinon), même
     * seul membre (décision 2026-11-27-partenaire-message-seul).
     */
    public function message(User $user, Partner $partner): Response
    {
        return $this->attached($user, $partner);
    }

    private function attached(User $user, Partner $partner): Response
    {
        if ((int) $partner->country_id !== (int) CurrentCountry::require()->getKey()) {
            return Response::denyAsNotFound();
        }
        if (! $partner->isAdministeredBy($user->getKey())) {
            return Response::denyAsNotFound();
        }

        return Response::allow();
    }
}
