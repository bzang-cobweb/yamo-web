<?php

namespace Modules\Auth\Services;

use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Modules\Auth\Enums\VerificationPurpose;
use Modules\Auth\Models\VerificationCode;
use Modules\Auth\Support\Identifiers;

/**
 * Mot de passe oublié : identifiant → code → nouveau mot de passe. La réponse ne
 * dit jamais si un compte existe ; le mot de passe ne change qu'avec un code validé
 * depuis moins de la durée d'un code, et toutes les sessions sont alors fermées.
 */
final class PasswordResetService
{
    public function __construct(private readonly VerificationCodeService $codeService) {}

    /**
     * Envoie le code si un compte correspond. Sinon, même réponse, sans rien envoyer :
     * un défi factice qu'aucun code ne validera.
     *
     * @param  array{identifier: string}  $data
     * @return array{challenge: string, channel: string, destination: string}
     */
    public function start(array $data): array
    {
        ['channel' => $channel, 'destination' => $destination, 'user' => $user] = Identifiers::resolve($data['identifier']);

        if ($user === null) {
            return [
                'challenge' => (string) Str::uuid(),
                'channel' => $channel->value,
                'destination' => VerificationCodeService::maskDestination($channel, $destination),
            ];
        }

        $challenge = $this->codeService->start(VerificationPurpose::RESET_PASSWORD, $user, [$channel->value => $destination]);

        return Challenges::describe($challenge, $this->codeService);
    }

    /**
     * Code validé : l'app affiche le formulaire du nouveau mot de passe. Le défi reste
     * ouvert jusqu'au changement.
     *
     * @return array{next: string, challenge: string}
     */
    public function confirm(VerificationCode $verified): array
    {
        return ['next' => 'password', 'challenge' => $verified->challenge];
    }

    /**
     * Nouveau mot de passe, pour un défi dont le code a été validé il y a moins de la
     * durée d'un code. Toutes les sessions de l'app sont fermées.
     *
     * @throws ValidationException défi inconnu, non validé ou expiré (erreur sur `code`)
     */
    public function reset(string $challenge, string $password): void
    {
        $verified = VerificationCode::query()->ofChallenge($challenge)
            ->where('purpose', VerificationPurpose::RESET_PASSWORD)
            ->where('verified_at', '>', now()->subMinutes($this->codeService->minutes()))
            ->first();

        if ($verified === null) {
            throw ValidationException::withMessages(['code' => __('auth::verification.errors.unknown')]);
        }

        $user = $verified->user;
        $user->forceFill(['password' => $password])->save();
        $user->tokens()->delete();
        $this->codeService->close($challenge);
    }
}
