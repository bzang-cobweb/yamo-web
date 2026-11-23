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
 * Au back-office, selon les permissions `partners.*` (décision 2026-11-23-module-partenaires). Dans l'app, tout
 * compte propose un partenaire ; les comptes qui y sont rattachés le modifient, ses administrateurs (`is_admin`)
 * seuls le retirent ; pour les autres, il n'existe pas (décision 2026-11-24-partenaires-par-les-membres).
 */
class PartnerPolicy
{
    use HasBeforeAbility;

    public function viewAny(User $user): bool
    {
        return Filament::isServing() && $user->hasPermissionTo(PermissionEnum::VIEW_PARTNERS);
    }

    public function view(User $user, Partner $partner): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return Filament::isServing() ? $user->hasPermissionTo(PermissionEnum::CREATE_PARTNERS) : true;
    }

    public function update(User $user, Partner $partner): bool|Response
    {
        return Filament::isServing() ? $user->hasPermissionTo(PermissionEnum::UPDATE_PARTNERS) : $this->administers($user, $partner);
    }

    public function delete(User $user, Partner $partner): bool|Response
    {
        if (Filament::isServing()) {
            return $user->hasPermissionTo(PermissionEnum::DELETE_PARTNERS);
        }

        // Retirer : un administrateur seulement (`is_admin`) ; un compte rattaché sans l'être est refusé (403).
        $attached = $this->administers($user, $partner);

        return $attached->denied() || $partner->hasAdmin($user->getKey()) ? $attached : Response::deny();
    }

    /** Un compte rattaché, dans le pays courant ; sinon 404, comme un partenaire qui n'existe pas. */
    private function administers(User $user, Partner $partner): Response
    {
        return (int) $partner->country_id === (int) CurrentCountry::require()->getKey() && $partner->isAdministeredBy($user->getKey())
            ? Response::allow()
            : Response::denyAsNotFound();
    }

    public function deleteAny(User $user): bool
    {
        return Filament::isServing() && $user->hasPermissionTo(PermissionEnum::DELETE_PARTNERS);
    }
}
