<?php

namespace Modules\Auth\Services;

use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Modules\Auth\Models\User;

/**
 * Connexion de l'app mobile : vérifie les identifiants et crée un jeton Sanctum
 * par appareil.
 */
final class LoginService
{
    /**
     * @param  array{email: string, password: string, device_name: string}  $data
     * @return array{user: User, token: string}
     *
     * @throws ValidationException identifiants refusés, sans dire lequel est faux
     */
    public function login(array $data): array
    {
        $guard = Auth::guard('web');

        if (! $guard->validate(['email' => $data['email'], 'password' => $data['password']])) {
            throw ValidationException::withMessages(['email' => __('auth.failed')]);
        }

        /** @var User $user */
        $user = $guard->getLastAttempted();

        return [
            'user' => $user,
            'token' => $this->createToken($user, $data['device_name']),
        ];
    }

    /**
     * Jeton en clair, à transmettre une seule fois à l'appareil. Méthode typée :
     * `NewAccessToken::$plainTextToken` ne l'est pas, et le contrat OpenAPI en dépend.
     */
    private function createToken(User $user, string $deviceName): string
    {
        return $user->createToken($deviceName)->plainTextToken;
    }
}
