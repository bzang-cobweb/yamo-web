<?php

namespace Modules\Auth\Services;

use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Modules\Auth\Enums\VerificationChannel;
use Modules\Auth\Enums\VerificationPurpose;
use Modules\Auth\Models\User;
use Modules\Auth\Models\VerificationCode;
use Modules\Auth\Support\PhoneNumbers;

/**
 * Connexion de l'app mobile, en deux étapes : identifiants (e-mail ou téléphone
 * et mot de passe), puis code envoyé à cet e-mail ou ce téléphone. Le jeton Sanctum
 * n'est créé qu'une fois le code validé (décision 2026-11-17-contrat-pays-telephone-codes).
 */
final class LoginService
{
    public function __construct(private readonly VerificationCodeService $codes) {}

    /**
     * Vérifie les identifiants et envoie le code.
     *
     * @param  array{identifier: string, password: string}  $data
     * @return array{challenge: string, channel: string, destination: string}
     *
     * @throws ValidationException identifiants refusés, sans dire lequel est faux
     */
    public function start(array $data): array
    {
        [$user, $channel] = $this->find($data['identifier']);

        if ($user === null || ! Hash::check($data['password'], $user->password)) {
            throw ValidationException::withMessages(['identifier' => __('auth.failed')]);
        }

        $destination = $channel === VerificationChannel::EMAIL ? (string) $user->email : (string) $user->phone;
        $challenge = $this->codes->start(VerificationPurpose::LOGIN, $user, [$channel->value => $destination]);

        return Challenges::describe($challenge, $this->codes);
    }

    /**
     * Code validé : jeton de l'appareil.
     *
     * @return array{user: User, token: string}
     */
    public function complete(VerificationCode $verified, string $deviceName): array
    {
        $user = $verified->user;
        $this->codes->close($verified->challenge);

        return ['user' => $user, 'token' => Challenges::token($user, $deviceName)];
    }

    /**
     * Compte d'après un e-mail, ou un téléphone vérifié au format international.
     *
     * @return array{0: User|null, 1: VerificationChannel}
     */
    private function find(string $identifier): array
    {
        if (str_contains($identifier, '@')) {
            return [User::query()->where('email', $identifier)->first(), VerificationChannel::EMAIL];
        }

        $phone = PhoneNumbers::fromInternational($identifier);

        return [
            $phone === null ? null : User::query()->where('phone', $phone)->whereNotNull('phone_verified_at')->first(),
            VerificationChannel::SMS,
        ];
    }
}
