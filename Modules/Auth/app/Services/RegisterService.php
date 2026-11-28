<?php

namespace Modules\Auth\Services;

use Illuminate\Support\Facades\App;
use Illuminate\Validation\ValidationException;
use Modules\Auth\Enums\RoleEnum;
use Modules\Auth\Enums\VerificationChannel;
use Modules\Auth\Enums\VerificationPurpose;
use Modules\Auth\Models\Role;
use Modules\Auth\Models\User;
use Modules\Auth\Models\VerificationCode;
use Modules\Auth\Support\PhoneNumbers;
use Modules\Auth\Support\PhoneOwnership;
use Modules\Auth\Support\Pseudonyms;

/**
 * Inscription depuis l'app, dernière étape du parcours (décision 2026-11-28-connexion-panneau) : l'identifiant a été
 * prouvé par un code (défi `REGISTER` ouvert par `POST /auth/login`), le mot de passe choisi (`POST /auth/session`) ;
 * restent le prénom, le nom et l'autre moyen de contact, facultatif et non vérifié.
 */
final class RegisterService
{
    public function __construct(private readonly VerificationCodeService $codeService) {}

    /**
     * Le canal de l'identifiant prouvé d'un défi d'inscription (`email` ou `phone`), pour choisir l'autre contact du
     * formulaire ; `null` pour un défi inconnu.
     */
    public static function channelOf(string $challenge): ?string
    {
        $code = VerificationCode::query()->ofChallenge($challenge)->where('purpose', VerificationPurpose::REGISTER)->first();

        return match ($code?->channel) {
            VerificationChannel::EMAIL => 'email',
            VerificationChannel::SMS => 'phone',
            null => null,
        };
    }

    /**
     * Crée le compte : identifiant vérifié, autre contact non vérifié, pseudonyme tiré du prénom et du nom, langue de
     * la requête, rôle membre. Le défi est clos.
     *
     * @param  array{challenge: string, first_name: string, last_name: string, birth_date?: string|null, email?: string|null, phone?: string|null, phone_country?: string|null}  $data
     * @return array{user: User, token: string}
     *
     * @throws ValidationException défi inutilisable ou sans mot de passe, valeur prise entre-temps (erreur sur `code`)
     */
    public function register(array $data, string $deviceName): array
    {
        $verified = Challenges::verified($data['challenge'], [VerificationPurpose::REGISTER], $this->codeService);
        $payload = (array) $verified->payload;

        if (! isset($payload['password'])) {
            throw ValidationException::withMessages(['code' => __('auth::form.session.password_missing')]);
        }

        $byPhone = $verified->channel === VerificationChannel::SMS;
        $contact = $this->otherContact($byPhone, $data);
        $attributes = [
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            // Seulement avec un âge minimum réglé (formulaire `auth.register`).
            'birth_date' => filled($data['birth_date'] ?? null) ? $data['birth_date'] : null,
            'email' => $byPhone ? $contact['email'] : $verified->destination,
            'phone' => $byPhone ? $verified->destination : $contact['phone'],
            'phone_country_id' => $byPhone ? ($payload['phone_country_id'] ?? null) : $contact['phone_country_id'],
            'locale' => App::getLocale(),
        ];

        $user = Challenges::persist($byPhone ? 'phone' : 'email', function () use ($attributes, $byPhone, $payload, $verified): User {
            Challenges::ensureFree('email', $attributes['email']);
            // L'autre contact n'est pas prouvé : il ne peut retirer le numéro à personne (décision 2026-11-28-numero-non-verifie).
            Challenges::ensureFree('phone', $attributes['phone'], proven: $byPhone);
            if ($byPhone) {
                // Numéro prouvé par SMS : retiré du compte qui le portait sans l'avoir vérifié, ou du compte inactif.
                PhoneOwnership::release($attributes['phone']);
            }

            $user = new User([...$attributes, 'name' => Pseudonyms::generate($attributes['first_name'], $attributes['last_name'])]);
            // Seul l'identifiant prouvé par le code est vérifié ; le mot de passe est déjà haché.
            $user->forceFill([
                'password' => $payload['password'],
                'email_verified_at' => $byPhone ? null : now(),
                'phone_verified_at' => $byPhone ? now() : null,
            ])->save();
            // Inscrit depuis l'app : membre (décision 2026-11-18-role-membre). Le rôle est créé s'il manque, pour ne
            // pas dépendre d'un seeder rejoué.
            $user->assignRole(Role::findOrCreate(RoleEnum::MEMBER->value, 'web'));
            $this->codeService->close($verified->challenge);

            return $user;
        });

        return ['user' => $user, 'token' => Challenges::token($user, $deviceName)];
    }

    /**
     * L'autre moyen de contact saisi : le téléphone (E.164 et son pays) pour une inscription par e-mail, l'e-mail pour
     * une inscription par téléphone ; `null` s'il est laissé vide.
     *
     * @param  array<string, mixed>  $data
     * @return array{email: string|null, phone: string|null, phone_country_id: int|null}
     */
    private function otherContact(bool $byPhone, array $data): array
    {
        if ($byPhone) {
            return ['email' => filled($data['email'] ?? null) ? trim((string) $data['email']) : null, 'phone' => null, 'phone_country_id' => null];
        }

        $country = PhoneNumbers::country($data['phone_country'] ?? null);
        $phone = filled($data['phone'] ?? null) && $country !== null ? PhoneNumbers::toE164((string) $data['phone'], $country->code) : null;

        return ['email' => null, 'phone' => $phone, 'phone_country_id' => $phone === null ? null : $country->getKey()];
    }
}
