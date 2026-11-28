<?php

namespace Modules\Auth\Tests\Concerns;

use Illuminate\Testing\TestResponse;
use Modules\Auth\Support\PhoneNumbers;

/**
 * Le parcours « Se connecter ou s'inscrire » (décision 2026-11-28-connexion-panneau) : identifiant, code, mot de passe,
 * inscription. À utiliser avec `CapturesVerificationCodes`.
 */
trait SignsUpWithCode
{
    protected string $newPassword = 'Le-bon-mot-2026';

    /**
     * L'identifiant saisi (`email`, ou `phone` et `phone_country`) : le défi (202).
     *
     * @param  array<string, string>  $identifier
     */
    protected function startWith(array $identifier): string
    {
        $channel = isset($identifier['email']) ? 'email' : 'phone';

        return $this->postJson('/api/v1/auth/login', ['channel' => $channel, ...$identifier])->assertAccepted()->json('challenge');
    }

    /**
     * Le code reçu par l'identifiant, validé.
     *
     * @param  array<string, string>  $identifier
     */
    protected function verifyCodeFor(string $challenge, array $identifier): TestResponse
    {
        $code = isset($identifier['email'])
            ? $this->emailCode($identifier['email'])
            : $this->smsCode(PhoneNumbers::toE164($identifier['phone'], $identifier['phone_country']));

        return $this->postJson('/api/v1/auth/codes/verify', ['challenge' => $challenge, 'code' => $code]);
    }

    /**
     * Identifiant inconnu, code validé, mot de passe choisi : le défi prêt pour `POST /auth/register`.
     *
     * @param  array<string, string>  $identifier
     */
    protected function registerChallenge(array $identifier): string
    {
        $challenge = $this->startWith($identifier);
        $this->verifyCodeFor($challenge, $identifier)->assertOk()->assertJsonPath('mode', 'register');
        $this->postJson('/api/v1/auth/session', ['challenge' => $challenge, 'password' => $this->newPassword, 'password_confirmation' => $this->newPassword])
            ->assertOk()->assertExactJson(['next' => 'register', 'challenge' => $challenge]);

        return $challenge;
    }

    /**
     * Tout le parcours d'inscription ; la réponse de `POST /auth/register`.
     *
     * @param  array<string, string>  $identifier
     * @param  array<string, mixed>  $data  prénom, nom, autre contact…
     */
    protected function signUp(array $identifier, array $data = []): TestResponse
    {
        return $this->postJson('/api/v1/auth/register', [
            'challenge' => $this->registerChallenge($identifier),
            'first_name' => 'Ada',
            'last_name' => 'Martin',
            'device_name' => 'iPhone',
            ...$data,
        ]);
    }
}
