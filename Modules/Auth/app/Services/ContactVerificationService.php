<?php

namespace Modules\Auth\Services;

use Illuminate\Validation\ValidationException;
use Modules\Auth\Enums\VerificationChannel;
use Modules\Auth\Enums\VerificationPurpose;
use Modules\Auth\Models\User;
use Modules\Auth\Models\VerificationCode;

/**
 * Vérifier l'e-mail ou le numéro actuel du compte (décision 2026-11-27-verifier-email-telephone) : un code part à
 * l'adresse (ou au numéro) enregistrée ; sa validation pose `email_verified_at` (ou `phone_verified_at`). Les
 * formulaires `auth.profile.email.verify` et `auth.profile.phone.verify` la partagent : même opération, deux canaux.
 */
final class ContactVerificationService
{
    public function __construct(private readonly VerificationCodeService $codeService) {}

    /**
     * @return array{challenge: string, channel: string, destination: string}
     *
     * @throws ValidationException rien à vérifier, ou déjà vérifié (erreur sur `email` ou `phone`)
     */
    public function start(User $user, VerificationChannel $channel): array
    {
        [$field, $value, $verifiedAt, $purpose] = $channel === VerificationChannel::EMAIL
            ? ['email', $user->email, $user->email_verified_at, VerificationPurpose::VERIFY_EMAIL]
            : ['phone', $user->phone, $user->phone_verified_at, VerificationPurpose::VERIFY_PHONE];

        if ($value === null || $value === '') {
            throw ValidationException::withMessages([$field => __('auth::verification.errors.missing')]);
        }

        if ($verifiedAt !== null) {
            throw ValidationException::withMessages([$field => __('auth::verification.errors.already_verified')]);
        }

        $challenge = $this->codeService->start($purpose, $user, [$channel->value => $value]);

        return Challenges::describe($challenge, $this->codeService);
    }

    /**
     * Le code validé : la valeur est vérifiée, si elle n'a pas changé entre-temps.
     *
     * @throws ValidationException l'adresse (ou le numéro) a changé depuis l'envoi (erreur sur `code`)
     */
    public function complete(VerificationCode $verified): User
    {
        $user = $verified->user;
        [$column, $stamp] = $verified->purpose === VerificationPurpose::VERIFY_EMAIL
            ? ['email', 'email_verified_at']
            : ['phone', 'phone_verified_at'];

        if ($user->{$column} !== $verified->destination) {
            $this->codeService->close($verified->challenge);

            throw ValidationException::withMessages(['code' => __('auth::verification.errors.unknown')]);
        }

        $user->forceFill([$stamp => now()])->save();
        $this->codeService->close($verified->challenge);

        return $user;
    }
}
