<?php

namespace Modules\Auth\Services;

use Illuminate\Validation\ValidationException;
use Modules\Auth\Enums\VerificationChannel;
use Modules\Auth\Enums\VerificationPurpose;
use Modules\Auth\Models\User;
use Modules\Auth\Models\UserPhone;
use Modules\Auth\Models\VerificationCode;
use Modules\Auth\Support\PhoneNumbers;
use Modules\Auth\Support\PhoneOwnership;

/**
 * Les numéros supplémentaires d'un compte (décision 2026-11-27-plusieurs-numeros) : le principal reste `users.phone`.
 * - Ajouter : un code part par SMS au numéro, qui n'est enregistré, vérifié, qu'une fois le code validé ;
 * - retirer : l'entrée est supprimée ;
 * - rendre principal : il prend la place de `users.phone`, l'ancien principal rejoint les numéros supplémentaires.
 */
final class UserPhoneService
{
    public function __construct(private readonly VerificationCodeService $codeService) {}

    /**
     * Pourquoi ce numéro (E.164) ne peut pas être ajouté au compte, ou `null` s'il le peut : déjà son numéro
     * principal, déjà dans ses numéros, ou tenu par un autre compte (principal ou supplémentaire ; un compte inactif
     * le cède, décision 2026-11-20-restauration-numero-recycle, de même un numéro non vérifié, décision
     * 2026-11-28-numero-non-verifie).
     */
    public static function refusal(User $user, string $phone): ?string
    {
        if ($user->phone === $phone) {
            return __('auth::phones.errors.primary');
        }

        if ($user->phones()->where('phone', $phone)->exists()) {
            return __('auth::phones.errors.duplicate');
        }

        if (PhoneOwnership::isTaken($phone, $user->getKey()) || PhoneOwnership::isTakenAsExtra($phone, $user->getKey())) {
            return __('validation.unique', ['attribute' => __('auth::user.phone')]);
        }

        return null;
    }

    /**
     * @param  array{phone: string, phone_country: string}  $data
     * @return array{challenge: string, channel: string, destination: string}
     */
    public function start(User $user, array $data): array
    {
        $country = PhoneNumbers::country($data['phone_country']);
        $phone = PhoneNumbers::toE164($data['phone'], (string) $country?->code);

        $challenge = $this->codeService->start(
            VerificationPurpose::ADD_PHONE,
            $user,
            [VerificationChannel::SMS->value => $phone],
            ['phone_country_id' => $country?->getKey()],
        );

        return Challenges::describe($challenge, $this->codeService);
    }

    /**
     * Le code validé : le numéro rejoint ceux du compte, vérifié. Pris entre-temps (par ce compte ou un autre) : refusé
     * sur `code`.
     */
    public function complete(VerificationCode $verified): User
    {
        $user = $verified->user;

        Challenges::persist('phone', function () use ($user, $verified): void {
            $refusal = self::refusal($user, $verified->destination);

            if ($refusal !== null) {
                $this->codeService->close($verified->challenge);

                throw ValidationException::withMessages(['code' => $refusal]);
            }

            // Prouvé par SMS : retiré du compte qui le portait sans l'avoir vérifié, ou du compte inactif.
            PhoneOwnership::release($verified->destination, $user->getKey());
            $user->phones()->create([
                'phone' => $verified->destination,
                'phone_country_id' => ((array) $verified->payload)['phone_country_id'] ?? null,
                'verified_at' => now(),
            ]);
            $this->codeService->close($verified->challenge);
        });

        return $user->unsetRelation('phones');
    }

    public function remove(UserPhone $phone): void
    {
        $phone->delete();
    }

    /**
     * Le numéro devient le principal du compte (connexion par SMS, codes) ; l'ancien principal, s'il y en a un, rejoint
     * les numéros supplémentaires avec son état vérifié. Aucun code : le numéro a été prouvé à l'ajout. Un autre compte
     * qui l'a pris comme principal depuis, même inactif, le garde : refusé sur `phone`.
     *
     * @throws ValidationException numéro principal d'un autre compte
     */
    public function makePrimary(UserPhone $extra): User
    {
        $user = $extra->user;

        return Challenges::persist('phone', function () use ($user, $extra): User {
            if (User::withTrashed()->where('phone', $extra->phone)->whereKeyNot($user->getKey())->exists()) {
                throw ValidationException::withMessages(['phone' => __('validation.unique', ['attribute' => __('auth::user.phone')])]);
            }

            $previous = $user->only(['phone', 'phone_country_id', 'phone_verified_at']);

            $extra->delete();
            $user->forceFill([
                'phone' => $extra->phone,
                'phone_country_id' => $extra->phone_country_id,
                'phone_verified_at' => $extra->verified_at,
            ])->save();

            if (filled($previous['phone'])) {
                $user->phones()->create([
                    'phone' => $previous['phone'],
                    'phone_country_id' => $previous['phone_country_id'],
                    'verified_at' => $previous['phone_verified_at'],
                ]);
            }

            return $user->unsetRelation('phones')->unsetRelation('phoneCountry');
        });
    }
}
