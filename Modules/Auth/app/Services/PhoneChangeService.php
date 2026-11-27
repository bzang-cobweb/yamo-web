<?php

namespace Modules\Auth\Services;

use Modules\Auth\Enums\VerificationChannel;
use Modules\Auth\Enums\VerificationPurpose;
use Modules\Auth\Models\User;
use Modules\Auth\Models\VerificationCode;
use Modules\Auth\Support\PhoneNumbers;
use Modules\Auth\Support\PhoneOwnership;

/**
 * Ajouter ou changer son téléphone : un code part par SMS au nouveau numéro, qui
 * n'est enregistré qu'une fois le code validé.
 */
final class PhoneChangeService
{
    public function __construct(private readonly VerificationCodeService $codeService) {}

    /**
     * @param  array{phone: string, phone_country: string}  $data
     * @return array{challenge: string, channel: string, destination: string}
     */
    public function start(User $user, array $data): array
    {
        $country = PhoneNumbers::country($data['phone_country']);
        $phone = PhoneNumbers::toE164($data['phone'], (string) $country?->code);

        $challenge = $this->codeService->start(
            VerificationPurpose::CHANGE_PHONE,
            $user,
            [VerificationChannel::SMS->value => $phone],
            ['phone_country_id' => $country?->getKey()],
        );

        return Challenges::describe($challenge, $this->codeService);
    }

    public function complete(VerificationCode $verified): User
    {
        $user = $verified->user;

        Challenges::persist('phone', function () use ($user, $verified): void {
            Challenges::ensureFree('phone', $verified->destination, $user->getKey());
            // Numéro prouvé par SMS : retiré du compte inactif qui le portait encore.
            PhoneOwnership::release($verified->destination, $user->getKey());
            // Un de ses numéros supplémentaires devenu le principal n'y figure plus (décision 2026-11-27-plusieurs-numeros).
            $user->phones()->where('phone', $verified->destination)->delete();
            $user->forceFill([
                'phone' => $verified->destination,
                'phone_country_id' => ((array) $verified->payload)['phone_country_id'] ?? null,
                'phone_verified_at' => now(),
            ])->save();
            $this->codeService->close($verified->challenge);
        });

        return $user->unsetRelation('phoneCountry');
    }
}
