<?php

namespace Modules\Auth\Services;

use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Auth\Enums\RoleEnum;
use Modules\Auth\Enums\VerificationChannel;
use Modules\Auth\Enums\VerificationPurpose;
use Modules\Auth\Models\AccountDeletionReason;
use Modules\Auth\Models\User;
use Modules\Auth\Models\VerificationCode;

/**
 * Suppression d'un compte par son titulaire, confirmée par une raison et un code (décision
 * 2026-11-27-confidentialite) : récupérable pendant
 * `config('auth.account_deletion.grace_days')` jours, purgée ensuite
 * (décision 2026-11-20-suppression-de-son-compte).
 */
class AccountDeletionService
{
    public function __construct(private readonly VerificationCodeService $codeService) {}

    /**
     * Demander la suppression : la raison est gardée avec le défi, et un code part à l'e-mail vérifié, sinon au numéro
     * vérifié, sinon à l'e-mail ou au numéro du compte (décision 2026-11-27-confidentialite). Rien n'est supprimé
     * avant la validation du code (`complete`).
     *
     * @return array{challenge: string, channel: string, destination: string}
     *
     * @throws ValidationException dernier super administrateur, ou ni e-mail ni numéro (erreur sur `reason`)
     */
    public function start(User $user, string $reason): array
    {
        if ($user->isSuperAdmin() && $this->isLastSuperAdmin($user)) {
            throw ValidationException::withMessages(['reason' => __('auth::form.delete_account.last_super_admin')]);
        }

        $destination = $this->destination($user)
            ?? throw ValidationException::withMessages(['reason' => __('auth::form.delete_account.no_destination')]);

        $challenge = $this->codeService->start(VerificationPurpose::DELETE_ACCOUNT, $user, $destination, ['reason' => $reason]);

        return Challenges::describe($challenge, $this->codeService);
    }

    /**
     * Le code validé : la raison est enregistrée pour l'équipe, puis le compte est désactivé comme `delete()`.
     *
     * @throws ValidationException le dernier super administrateur ne se supprime pas (erreur sur `code`)
     */
    public function complete(VerificationCode $verified): User
    {
        $user = $verified->user;

        DB::transaction(function () use ($user, $verified): void {
            $this->delete($user, 'code');
            AccountDeletionReason::query()->create(['user_id' => $user->getKey(), 'reason' => (string) ($verified->payload['reason'] ?? '')]);
            $this->codeService->close($verified->challenge);
        });

        return $user;
    }

    /**
     * Désactive le compte : il ne se connecte plus, ses jetons disparaissent
     * (`User::booted`), ses données restent restaurables jusqu'à la purge.
     *
     * @param  string  $errorKey  le champ qui porte le refus
     *
     * @throws ValidationException le dernier super administrateur ne se supprime pas
     */
    public function delete(User $user, string $errorKey = 'reason'): void
    {
        DB::transaction(function () use ($user, $errorKey): void {
            if ($user->isSuperAdmin() && $this->isLastSuperAdmin($user)) {
                throw ValidationException::withMessages([
                    $errorKey => __('auth::form.delete_account.last_super_admin'),
                ]);
            }

            $user->delete();
        });
    }

    /**
     * Où envoyer le code : l'e-mail vérifié, le numéro vérifié, puis l'e-mail ou le numéro tels quels ; `null` sans
     * aucun des deux.
     *
     * @return array<string, string>|null canal => destination
     */
    private function destination(User $user): ?array
    {
        return match (true) {
            $user->email !== null && $user->email_verified_at !== null => [VerificationChannel::EMAIL->value => $user->email],
            $user->phone !== null && $user->phone_verified_at !== null => [VerificationChannel::SMS->value => $user->phone],
            $user->email !== null => [VerificationChannel::EMAIL->value => $user->email],
            $user->phone !== null => [VerificationChannel::SMS->value => $user->phone],
            default => null,
        };
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
