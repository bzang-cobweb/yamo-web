<?php

namespace Modules\Auth\Services;

use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Hash;
use Modules\Auth\Enums\RoleEnum;
use Modules\Auth\Enums\VerificationChannel;
use Modules\Auth\Enums\VerificationPurpose;
use Modules\Auth\Models\Role;
use Modules\Auth\Models\User;
use Modules\Auth\Models\VerificationCode;
use Modules\Auth\Support\PhoneNumbers;
use Modules\Auth\Support\PhoneOwnership;

/**
 * Inscription depuis l'app : un code part vers l'e-mail et/ou le téléphone saisis ;
 * le compte est créé, identifiants vérifiés, quand tous les codes sont validés.
 */
final class RegisterService
{
    public function __construct(private readonly VerificationCodeService $codeService) {}

    /**
     * @param  array{name: string, first_name: string, last_name: string, gender: string, birth_date?: string|null, description?: string|null, email?: string|null, phone?: string|null, phone_country?: string|null, password: string}  $data
     * @return array{challenge: string, channel: string, destination: string}
     */
    public function start(array $data): array
    {
        $country = PhoneNumbers::country($data['phone_country'] ?? null);
        $phone = filled($data['phone'] ?? null) && $country !== null ? PhoneNumbers::toE164($data['phone'], $country->code) : null;
        $email = filled($data['email'] ?? null) ? $data['email'] : null;

        $challenge = $this->codeService->start(
            VerificationPurpose::REGISTER,
            null,
            array_filter([VerificationChannel::EMAIL->value => $email, VerificationChannel::SMS->value => $phone]),
            [
                'name' => $data['name'],
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'gender' => $data['gender'],
                'birth_date' => filled($data['birth_date'] ?? null) ? $data['birth_date'] : null,
                'description' => filled($data['description'] ?? null) ? $data['description'] : null,
                'email' => $email,
                'phone' => $phone,
                'phone_country_id' => $phone === null ? null : $country?->getKey(),
                'password' => Hash::make($data['password']),
                'locale' => App::getLocale(),
            ],
        );

        return Challenges::describe($challenge, $this->codeService);
    }

    /**
     * Après chaque code validé : le prochain code à saisir, ou le compte créé.
     *
     * @return array{challenge: string, channel: string, destination: string}|array{user: User, token: string}
     */
    public function complete(VerificationCode $verified, string $deviceName): array
    {
        if ($this->codeService->nextPending($verified->challenge) !== null) {
            return Challenges::describe($verified->challenge, $this->codeService);
        }

        $payload = (array) $verified->payload;

        $user = Challenges::persist($payload['phone'] !== null ? 'phone' : 'email', function () use ($payload, $verified): User {
            Challenges::ensureFree('name', $payload['name']);
            Challenges::ensureFree('email', $payload['email']);
            Challenges::ensureFree('phone', $payload['phone']);
            if ($payload['phone'] !== null) {
                // Numéro prouvé par SMS : retiré du compte inactif qui le portait encore.
                PhoneOwnership::release($payload['phone']);
            }
            // Mot de passe déjà haché à l'ouverture du défi : le cast `hashed` le garde tel quel.
            $user = new User(collect($payload)->except('password')->all());
            $user->forceFill([
                'password' => $payload['password'],
                'email_verified_at' => $payload['email'] !== null ? now() : null,
                'phone_verified_at' => $payload['phone'] !== null ? now() : null,
            ])->save();
            // Inscrit depuis l'app : membre (décision 2026-11-18-role-membre). Le rôle
            // est créé s'il manque, pour ne pas dépendre d'un seeder rejoué.
            $user->assignRole(Role::findOrCreate(RoleEnum::MEMBER->value, 'web'));
            $this->codeService->close($verified->challenge);

            return $user;
        });

        return ['user' => $user, 'token' => Challenges::token($user, $deviceName)];
    }
}
