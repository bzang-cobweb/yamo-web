<?php

namespace Modules\Auth\Services;

use Closure;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Auth\Enums\VerificationPurpose;
use Modules\Auth\Models\User;
use Modules\Auth\Models\VerificationCode;
use Modules\Auth\Support\Masks;
use Modules\Auth\Support\PhoneOwnership;

/**
 * Ce que l'app reçoit d'un défi en cours : son identifiant et le prochain code à
 * saisir (canal, destination masquée).
 */
final class Challenges
{
    /**
     * @return array{challenge: string, channel: string, destination: string}
     */
    public static function describe(string $challenge, VerificationCodeService $codeService): array
    {
        $next = $codeService->nextPending($challenge);

        return [
            'challenge' => $challenge,
            'channel' => $next?->channel->value ?? '',
            'destination' => $next === null ? '' : Masks::destination($next->channel, $next->destination),
        ];
    }

    /**
     * Le défi de connexion ou d'inscription dont le code a été validé, à l'étape du mot de passe ou de l'inscription
     * (décision 2026-11-28-connexion-panneau). Il sert une seule fois (il est clos ensuite) et
     * `auth.verification_codes.password_minutes` minutes après la validation du code ; au-delà, il est clos.
     *
     * @param  list<VerificationPurpose>  $purposes
     *
     * @throws ValidationException défi inconnu, déjà servi, au code non validé, expiré, ou aux essais épuisés (erreur sur `code`)
     */
    public static function verified(string $challenge, array $purposes, VerificationCodeService $codeService): VerificationCode
    {
        $verified = VerificationCode::query()->ofChallenge($challenge)->whereIn('purpose', $purposes)->first();

        if ($verified === null || $verified->verified_at === null) {
            throw ValidationException::withMessages(['code' => __('auth::verification.errors.unknown')]);
        }

        if ($verified->verified_at->lt(now()->subMinutes((int) config('auth.verification_codes.password_minutes', 30)))) {
            $codeService->close($challenge);

            throw ValidationException::withMessages(['code' => __('auth::verification.errors.unknown')]);
        }

        if ($verified->attempts >= $codeService->maxAttempts()) {
            $codeService->close($challenge);

            throw ValidationException::withMessages(['code' => __('auth::verification.errors.unknown')]);
        }

        return $verified;
    }

    /**
     * Jeton Sanctum de l'appareil, rendu une fois le défi réussi.
     */
    public static function token(User $user, string $deviceName): string
    {
        return $user->createToken($deviceName)->plainTextToken;
    }

    /**
     * Entre l'envoi et la validation du code, un autre compte a pu prendre la valeur :
     * on refuse proprement plutôt que de buter sur l'index unique. Un compte
     * supprimé, en attente de purge, garde ses valeurs : il compte aussi. Un numéro
     * prouvé par code (`$proven`) est libre s'il n'est tenu que par un compte inactif
     * ou qui ne l'a pas vérifié (`PhoneOwnership::isTaken`) ; un numéro enregistré
     * sans preuve ne l'est que si personne ne le porte (`isHeld`).
     *
     * @throws ValidationException valeur déjà prise (erreur sur `code`)
     */
    public static function ensureFree(string $column, ?string $value, ?int $ignoreUserId = null, bool $proven = true): void
    {
        if ($value === null) {
            return;
        }

        $taken = match (true) {
            $column === 'phone' && $proven => PhoneOwnership::isTaken($value, $ignoreUserId),
            $column === 'phone' => PhoneOwnership::isHeld($value, $ignoreUserId),
            default => User::withTrashed()->where($column, $value)
                ->when($ignoreUserId !== null, fn ($query) => $query->whereKeyNot($ignoreUserId))
                ->exists(),
        };

        if ($taken) {
            throw ValidationException::withMessages(['code' => __('validation.unique', ['attribute' => __('auth::user.'.$column)])]);
        }
    }

    /**
     * Fin d'un défi qui enregistre un identifiant : tout ou rien. `ensureFree` refuse
     * proprement le cas courant ; si deux demandes valident la même valeur au même
     * instant, la seconde bute sur l'index unique et reçoit le même refus, sans rien
     * laisser à moitié fait (numéro retiré de l'ancien compte sans être attribué…).
     *
     * @template T
     *
     * @param  Closure(): T  $complete
     * @return T
     *
     * @throws ValidationException valeur prise entre-temps (erreur sur `code`)
     */
    public static function persist(string $column, Closure $complete): mixed
    {
        try {
            return DB::transaction($complete);
        } catch (UniqueConstraintViolationException) {
            throw ValidationException::withMessages(['code' => __('validation.unique', ['attribute' => __('auth::user.'.$column)])]);
        }
    }
}
