<?php

namespace Modules\Auth\Services;

use Bza\Form\Support\DisplayTime;
use Illuminate\Validation\ValidationException;
use Modules\Auth\Enums\VerificationChannel;
use Modules\Auth\Enums\VerificationPurpose;
use Modules\Auth\Models\VerificationCode;
use Modules\Auth\Support\Identifiers;
use Modules\Auth\Support\PhoneNumbers;
use Modules\Auth\Support\PhoneOwnership;

/**
 * Se connecter ou s'inscrire depuis l'app : identifiant (e-mail ou téléphone), puis code envoyé à cet identifiant,
 * puis mot de passe (`SessionService`), puis l'inscription pour un nouveau compte (`RegisterService`) — décision
 * 2026-11-28-connexion-panneau, qui remplace en partie 2026-11-27-connexion-sans-mot-de-passe. Le jeton Sanctum
 * n'est créé qu'au bout du parcours.
 */
final class LoginService
{
    public function __construct(private readonly VerificationCodeService $codeService) {}

    /**
     * Le code part toujours, compte connu ou non, et la réponse a la même forme : l'existence du compte n'est
     * révélée qu'après le code. Compte connu : défi `LOGIN` ; inconnu : défi `REGISTER`, sans compte, l'identifiant
     * dans son `payload`. Par SMS, seul un numéro principal vérifié trouve son compte (`Identifiers`) : un numéro saisi
     * sans être vérifié mène à l'inscription, qui le retire de ce compte (décision 2026-11-28-numero-non-verifie).
     *
     * @param  array{channel?: string|null, email?: string|null, phone?: string|null, phone_country?: string|null}  $data
     * @return array{challenge: string, channel: string, destination: string}
     */
    public function start(array $data): array
    {
        ['channel' => $channel, 'destination' => $destination, 'user' => $user] = Identifiers::resolve($data, withTrashed: true);

        // Numéro recyclé : le code SMS ouvrirait le compte inactif au nouveau titulaire du numéro. Traité comme
        // inconnu, comme pour le mot de passe oublié (décision 2026-11-20-restauration-numero-recycle) ; son
        // inscription libère le numéro.
        if ($user !== null && $channel === VerificationChannel::SMS && PhoneOwnership::isInactive($user)) {
            $user = null;
        }

        $challenge = $user !== null
            ? $this->codeService->start(VerificationPurpose::LOGIN, $user, [$channel->value => $destination])
            : $this->codeService->start(VerificationPurpose::REGISTER, null, [$channel->value => $destination], self::identifier($channel, $destination, $data));

        return Challenges::describe($challenge, $this->codeService);
    }

    /**
     * Code validé : l'étape du mot de passe, le défi restant ouvert (`POST /auth/session`). `mode` : `sign-in`
     * (compte avec mot de passe), `create` (compte sans mot de passe), `register` (pas de compte). Pour un compte,
     * l'identifiant prouvé par le code est marqué vérifié. Un compte en attente de suppression n'est pas connecté :
     * l'app propose de le récupérer (`POST /auth/account/restore`).
     *
     * @return array{next: 'password', challenge: string, mode: 'sign-in'|'create'|'register'}|array{next: 'restore', challenge: string, deletion_date: string, deletion_date_text: string}
     */
    public function verified(VerificationCode $verified): array
    {
        // Le compteur d'essais sert désormais aux mots de passe faux (`SessionService`).
        $verified->forceFill(['attempts' => 0])->save();

        if ($verified->purpose === VerificationPurpose::REGISTER) {
            $this->ensureFree($verified);

            return ['next' => 'password', 'challenge' => $verified->challenge, 'mode' => 'register'];
        }

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

        // Le code reçu par SMS prouve le numéro (demande du 2026-11-27) : seulement si sa vérification a été retirée
        // pendant que le code attendait, `start` ne trouvant que des numéros vérifiés.
        if ($verified->channel === VerificationChannel::SMS && $user->phone_verified_at === null && $user->phone === $verified->destination) {
            $user->forceFill(['phone_verified_at' => now()])->save();
        }

        // De même, le code reçu par e-mail prouve l'adresse (décision 2026-11-27-verifier-email-telephone).
        if ($verified->channel === VerificationChannel::EMAIL && $user->email_verified_at === null && $user->email === $verified->destination) {
            $user->forceFill(['email_verified_at' => now()])->save();
        }

        return ['next' => 'password', 'challenge' => $verified->challenge, 'mode' => $user->password === null ? 'create' : 'sign-in'];
    }

    /**
     * L'identifiant d'une inscription a pu être pris entre l'envoi et la validation du code (autre inscription, numéro
     * ajouté à un compte) : le défi est clos et le refus porte sur `code`.
     *
     * @throws ValidationException identifiant déjà pris
     */
    private function ensureFree(VerificationCode $verified): void
    {
        $phone = $verified->channel === VerificationChannel::SMS;

        try {
            Challenges::ensureFree($phone ? 'phone' : 'email', $verified->destination);

            if ($phone && PhoneOwnership::isTakenAsExtra($verified->destination)) {
                throw ValidationException::withMessages(['code' => __('validation.unique', ['attribute' => __('auth::user.phone')])]);
            }
        } catch (ValidationException $exception) {
            $this->codeService->close($verified->challenge);

            throw $exception;
        }
    }

    /**
     * L'identifiant d'une inscription, gardé avec son défi : l'e-mail, ou le numéro (E.164) et son pays.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private static function identifier(VerificationChannel $channel, string $destination, array $data): array
    {
        if ($channel === VerificationChannel::EMAIL) {
            return ['email' => $destination];
        }

        return ['phone' => $destination, 'phone_country_id' => PhoneNumbers::country((string) ($data['phone_country'] ?? ''))?->getKey()];
    }
}
