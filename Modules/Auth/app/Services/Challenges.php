<?php

namespace Modules\Auth\Services;

use Closure;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Auth\Models\User;
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
     * tenu par un compte inactif est libre (`PhoneOwnership`).
     *
     * @throws ValidationException valeur déjà prise (erreur sur `code`)
     */
    public static function ensureFree(string $column, ?string $value, ?int $ignoreUserId = null): void
    {
        if ($value === null) {
            return;
        }

        $taken = $column === 'phone'
            ? PhoneOwnership::isTaken($value, $ignoreUserId)
            : User::withTrashed()->where($column, $value)
                ->when($ignoreUserId !== null, fn ($query) => $query->whereKeyNot($ignoreUserId))
                ->exists();

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
