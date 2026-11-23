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
        return ! Filament::isServing() && in_array($ability, ['view', 'update', 'delete'], true) ? null : $this->superAdminBefore($user, $ability);
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

        return $partner->isVisible() || ($user !== null && $this->attached($user, $partner)->allowed())
            ? Response::allow()
            : Response::denyAsNotFound();
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

        return $attached->denied() || $partner->hasAdmin($user->getKey()) ? $attached : Response::deny();
    }

    public function deleteAny(User $user): bool
    {
        return Filament::isServing() && $user->hasPermissionTo(PermissionEnum::DELETE_PARTNERS);
    }

    /** Rattaché au partenaire (`partner_user`), dans le pays courant ; sinon 404, comme un partenaire qui n'existe pas. */
    private function attached(User $user, Partner $partner): Response
    {
        return (int) $partner->country_id === (int) CurrentCountry::require()->getKey() && $partner->isAdministeredBy($user->getKey())
            ? Response::allow()
            : Response::denyAsNotFound();
    }
}
