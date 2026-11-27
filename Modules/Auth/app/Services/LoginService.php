<?php

namespace Modules\Auth\Services;

use Bza\Form\Support\DisplayTime;
use Modules\Auth\Enums\VerificationChannel;
use Modules\Auth\Enums\VerificationPurpose;
use Modules\Auth\Models\User;
use Modules\Auth\Models\VerificationCode;
use Modules\Auth\Support\Identifiers;
use Modules\Auth\Support\PhoneOwnership;

/**
 * Connexion de l'app mobile, en deux étapes : e-mail ou téléphone, sans mot de passe,
 * puis code envoyé à cet e-mail ou ce téléphone. Le jeton Sanctum n'est créé qu'une
 * fois le code validé (décisions 2026-11-17-contrat-pays-telephone-codes et
 * 2026-11-27-connexion-sans-mot-de-passe). Un identifiant inconnu mène à l'inscription.
 */
final class LoginService
{
    public function __construct(private readonly VerificationCodeService $codeService) {}

    /**
     * Compte trouvé : envoie le code. Sinon, `next: register` et l'identifiant saisi,
     * que l'app renvoie avec l'inscription (formulaire `auth.register`).
     *
     * @param  array{channel?: string|null, email?: string|null, phone?: string|null, phone_country?: string|null}  $data
     * @return array{challenge: string, channel: string, destination: string}|array{next: string, values: array<string, string>}
     */
    public function start(array $data): array
    {
        ['channel' => $channel, 'destination' => $destination, 'field' => $field, 'user' => $user] = Identifiers::resolve($data, withTrashed: true);

        // Numéro recyclé : sans mot de passe, le code SMS ouvrirait le compte inactif au
        // nouveau titulaire du numéro. Traité comme inconnu, comme pour le mot de passe
        // oublié (décision 2026-11-20-restauration-numero-recycle).
        if ($user !== null && $channel === VerificationChannel::SMS && PhoneOwnership::isInactive($user)) {
            $user = null;
        }

        // Un numéro saisi mais jamais vérifié (compte créé au back-office) : le code SMS le
        // prouve et `complete()` pose `phone_verified_at` ; l'inscription le refuserait (déjà pris).
        if ($user === null && $channel === VerificationChannel::SMS) {
            $user = User::query()->where('phone', $destination)->whereNull('phone_verified_at')->first();
            $user = $user !== null && ! PhoneOwnership::isInactive($user) ? $user : null;
        }

        if ($user === null) {
            return ['next' => 'register', 'values' => self::identifier($field, $data)];
        }

        $challenge = $this->codeService->start(VerificationPurpose::LOGIN, $user, [$channel->value => $destination]);

        return Challenges::describe($challenge, $this->codeService);
    }

    /**
     * Code validé : jeton de l'appareil. Un compte en attente de suppression n'est
     * pas connecté : l'app propose de le récupérer (`POST /auth/account/restore`),
     * le défi reste ouvert jusque-là.
     *
     * @return array{user: User, token: string}|array{next: string, challenge: string, deletion_date: string}
     */
    public function complete(VerificationCode $verified, string $deviceName): array
    {
        $user = $verified->user;

        if ($user->trashed()) {
            return [
                'next' => 'restore',
                'challenge' => $verified->challenge,
                'deletion_date' => AccountDeletionService::purgeDate($user)->toDateString(),
                // La date formatée (« 12 avril 2027 », décision 2026-11-25-dates-formatees-serveur).
                'deletion_date_text' => DisplayTime::day(AccountDeletionService::purgeDate($user)),
            ];
        }

        // Le code reçu par SMS prouve le numéro (demande du 2026-11-27).
        if ($verified->channel === VerificationChannel::SMS && $user->phone_verified_at === null) {
            $user->forceFill(['phone_verified_at' => now()])->save();
        }

        $this->codeService->close($verified->challenge);

        return ['user' => $user, 'token' => Challenges::token($user, $deviceName)];
    }

    /**
     * L'identifiant saisi, seul : l'e-mail, ou le numéro et son pays.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, string>
     */
    private static function identifier(string $field, array $data): array
    {
        if ($field === 'email') {
            return ['email' => trim((string) $data['email'])];
        }

        return ['phone' => trim((string) ($data['phone'] ?? '')), 'phone_country' => strtoupper((string) ($data['phone_country'] ?? ''))];
    }
}
