<?php

namespace Modules\Auth\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Auth\Enums\RoleEnum;
use Modules\Auth\Models\User;

/**
 * Suppression d'un compte par son titulaire : récupérable pendant
 * `config('auth.account_deletion.grace_days')` jours, purgée ensuite
 * (décision 2026-11-20-suppression-de-son-compte).
 */
class AccountDeletionService
{
    /**
     * Désactive le compte : il ne se connecte plus, ses jetons disparaissent
     * (`User::booted`), ses données restent restaurables jusqu'à la purge.
     *
     * @throws ValidationException le dernier super administrateur ne se supprime pas
     */
    public function delete(User $user): void
    {
        DB::transaction(function () use ($user): void {
            if ($user->isSuperAdmin() && $this->isLastSuperAdmin($user)) {
                throw ValidationException::withMessages([
                    'current_password' => __('auth::form.delete_account.last_super_admin'),
                ]);
            }

            $user->delete();
        });
    }

    /**
     * Purge définitive des comptes supprimés depuis plus que le délai, avec
     * leur photo. Renvoie le nombre de comptes purgés.
     */
    public function purgeExpired(): int
    {
        $limit = now()->subDays((int) config('auth.account_deletion.grace_days'));
        $purged = 0;

        User::onlyTrashed()->where('deleted_at', '<=', $limit)->each(function (User $user) use (&$purged): void {
            $user->forceDelete();
            $purged++;
        });

        return $purged;
    }

    private function isLastSuperAdmin(User $user): bool
    {
        return ! User::role(RoleEnum::SUPER_ADMIN->value)->whereKeyNot($user->getKey())->lockForUpdate()->exists();
    }
}
