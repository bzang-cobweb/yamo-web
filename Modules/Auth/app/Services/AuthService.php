<?php

namespace Modules\Auth\Services;

use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\PersonalAccessToken;
use Modules\Auth\Models\User;

final class AuthService
{
    /**
     * Vérifie les identifiants et crée un jeton Sanctum pour l'appareil.
     *
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

    /**
     * Modifie son propre compte avec des données déjà validées.
     *
     * @param  array{locale?: string}  $data
     */
    public function updateMe(User $user, array $data): User
    {
        $user->update($data);

        return $user;
    }

    /**
     * Révoque le jeton qui a authentifié la requête, et lui seul : les autres
     * appareils restent connectés.
     */
    public function logout(User $user): void
    {
        $token = $user->currentAccessToken();

        if ($token instanceof PersonalAccessToken) {
            $token->delete();
        }
    }
}
