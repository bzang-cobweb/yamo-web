<?php

namespace Modules\Auth\Services;

use Modules\Auth\Enums\VerificationChannel;
use Modules\Auth\Enums\VerificationPurpose;
use Modules\Auth\Models\User;
use Modules\Auth\Models\VerificationCode;

/**
 * Ajouter ou changer son e-mail : un code part vers la nouvelle adresse, qui
 * n'est enregistrée qu'une fois le code validé.
 */
final class EmailChangeService
{
    public function __construct(private readonly VerificationCodeService $codeService) {}

    /**
     * @param  array{email: string}  $data
     * @return array{challenge: string, channel: string, destination: string}
     */
    public function start(User $user, array $data): array
    {
        $challenge = $this->codeService->start(VerificationPurpose::CHANGE_EMAIL, $user, [VerificationChannel::EMAIL->value => $data['email']]);

        return Challenges::describe($challenge, $this->codeService);
    }

    public function complete(VerificationCode $verified): User
    {
        $user = $verified->user;

        Challenges::persist('email', function () use ($user, $verified): void {
            Challenges::ensureFree('email', $verified->destination, $user->getKey());
            $user->forceFill(['email' => $verified->destination, 'email_verified_at' => now()])->save();
            $this->codeService->close($verified->challenge);
        });

        return $user;
    }
}
