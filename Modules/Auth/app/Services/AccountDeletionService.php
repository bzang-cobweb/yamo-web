<?php

namespace Modules\Auth\Services;

use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Auth\Enums\RoleEnum;
use Modules\Auth\Enums\VerificationPurpose;
use Modules\Auth\Models\User;
use Modules\Auth\Models\VerificationCode;

/**
 * Suppression d'un compte par son titulaire : récupérable pendant
 * `config('auth.account_deletion.grace_days')` jours, purgée ensuite
 * (décision 2026-11-20-suppression-de-son-compte).
 */
class AccountDeletionService
{
    public function __construct(private readonly VerificationCodeService $codeService) {}

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
     * Date de la purge définitive d'un compte supprimé.
     */
    public static function purgeDate(User $user): CarbonInterface
    {
        return $user->deleted_at->copy()->addDays((int) config('auth.account_deletion.grace_days'));
    }

    /**
     * Le titulaire récupère son compte : défi de connexion validé il y a moins de
     * la durée d'un code, sur un compte encore en attente de suppression. Le compte
     * est restauré et l'appareil connecté.
     *
     * @return array{user: User, token: string}
     *
     * @throws ValidationException défi inconnu, non validé, expiré, ou compte déjà actif (erreur sur `code`)
     */
    public function restore(string $challenge, string $deviceName): array
    {
        $verified = VerificationCode::query()->ofChallenge($challenge)
            ->where('purpose', VerificationPurpose::LOGIN)
            ->where('verified_at', '>', now()->subMinutes($this->codeService->minutes()))
            ->first();

        if ($verified === null || ! $verified->user?->trashed()) {
            throw ValidationException::withMessages(['code' => __('auth::verification.errors.unknown')]);
        }

        $user = $verified->user;
        $user->restore();
        $this->codeService->close($challenge);

        return ['user' => $user, 'token' => Challenges::token($user, $deviceName)];
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
