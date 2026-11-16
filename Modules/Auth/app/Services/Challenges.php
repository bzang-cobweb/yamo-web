<?php

namespace Modules\Auth\Services;

use Illuminate\Validation\ValidationException;
use Modules\Auth\Models\User;

/**
 * Ce que l'app reçoit d'un défi en cours : son identifiant et le prochain code à
 * saisir (canal, destination masquée).
 */
final class Challenges
{
    /**
     * @return array{challenge: string, channel: string, destination: string}
     */
    public static function describe(string $challenge, VerificationCodeService $codes): array
    {
        $next = $codes->nextPending($challenge);

        return [
            'challenge' => $challenge,
            'channel' => $next?->channel->value ?? '',
            'destination' => $next === null ? '' : VerificationCodeService::mask($next),
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
     * on refuse proprement plutôt que de buter sur l'index unique.
     *
     * @throws ValidationException valeur déjà prise (erreur sur `code`)
     */
    public static function ensureFree(string $column, ?string $value, ?int $ignoreUserId = null): void
    {
        if ($value === null) {
            return;
        }

        $taken = User::query()->where($column, $value)
            ->when($ignoreUserId !== null, fn ($query) => $query->whereKeyNot($ignoreUserId))
            ->exists();

        if ($taken) {
            throw ValidationException::withMessages(['code' => __('validation.unique', ['attribute' => __('auth::user.'.$column)])]);
        }
    }
}
