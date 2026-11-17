<?php

namespace Modules\Auth\Services;

use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Modules\Auth\Enums\VerificationPurpose;
use Modules\Auth\Models\User;
use Modules\Auth\Models\VerificationCode;
use Modules\Auth\Support\Identifiers;

/**
 * Connexion de l'app mobile, en deux étapes : identifiants (e-mail ou téléphone
 * et mot de passe), puis code envoyé à cet e-mail ou ce téléphone. Le jeton Sanctum
 * n'est créé qu'une fois le code validé (décision 2026-11-17-contrat-pays-telephone-codes).
 */
final class LoginService
{
    public function __construct(private readonly VerificationCodeService $codeService) {}

    /**
     * Vérifie les identifiants et envoie le code.
     *
     * @param  array{email?: string|null, phone?: string|null, phone_country?: string|null, password: string}  $data
     * @return array{challenge: string, channel: string, destination: string}
     *
     * @throws ValidationException identifiants refusés (sur le champ saisi), sans dire lequel est faux
     */
    public function start(array $data): array
    {
        ['channel' => $channel, 'destination' => $destination, 'field' => $field, 'user' => $user] = Identifiers::resolve($data);

        if ($user === null || ! Hash::check($data['password'], $user->password)) {
            throw ValidationException::withMessages([$field => __('auth.failed')]);
        }

        $challenge = $this->codeService->start(VerificationPurpose::LOGIN, $user, [$channel->value => $destination]);

        return Challenges::describe($challenge, $this->codeService);
    }

    /**
     * Code validé : jeton de l'appareil.
     *
     * @return array{user: User, token: string}
     */
    public function complete(VerificationCode $verified, string $deviceName): array
    {
        $user = $verified->user;
        $this->codeService->close($verified->challenge);

        return ['user' => $user, 'token' => Challenges::token($user, $deviceName)];
    }
}
