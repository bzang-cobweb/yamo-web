<?php

namespace Modules\Auth\Services;

use Bza\Form\Support\Form;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Modules\Auth\Enums\VerificationPurpose;
use Modules\Auth\Forms\SessionNewPasswordForm;
use Modules\Auth\Forms\SessionPasswordForm;
use Modules\Auth\Models\User;
use Modules\Auth\Models\VerificationCode;

/**
 * L'étape du mot de passe, après le code de connexion ou d'inscription (décision 2026-11-28-connexion-panneau) :
 * - `sign-in` : le mot de passe du compte, ou un nouveau avec `reset` (« Mot de passe oublié ? » : le code vient de
 *   prouver l'identifiant) ;
 * - `create` : le compte n'en a pas encore (inscrit sans, le 2026-11-27) : il le choisit ;
 * - `register` : pas de compte, le mot de passe choisi est gardé haché avec le défi jusqu'à l'inscription.
 * Un mot de passe faux ne ferme pas le défi mais compte les essais : au cinquième, il est clos.
 */
final class SessionService
{
    public function __construct(private readonly VerificationCodeService $codeService) {}

    /**
     * @param  array{challenge: string, password?: mixed, password_confirmation?: mixed, reset?: bool|null}  $data
     * @return array{user: User, token: string}|array{next: 'register', challenge: string}
     *
     * @throws ValidationException défi inutilisable (`code`), mot de passe faux ou refusé (`password`)
     */
    public function open(array $data, string $deviceName): array
    {
        $verified = Challenges::verified($data['challenge'], [VerificationPurpose::LOGIN, VerificationPurpose::REGISTER], $this->codeService);

        if ($verified->purpose === VerificationPurpose::REGISTER) {
            $password = $this->validated(new SessionNewPasswordForm, $data);
            $verified->forceFill(['payload' => [...(array) $verified->payload, 'password' => Hash::make($password)]])->save();

            return ['next' => 'register', 'challenge' => $verified->challenge];
        }

        $user = $verified->user;

        // Un compte en attente de suppression se récupère (`POST /auth/account/restore`), il ne se connecte pas ici.
        if ($user === null || $user->trashed()) {
            throw ValidationException::withMessages(['code' => __('auth::verification.errors.unknown')]);
        }

        if ($user->password === null) {
            $user->forceFill(['password' => $this->validated(new SessionNewPasswordForm, $data)])->save();
        } elseif ($data['reset'] ?? false) {
            $user->forceFill(['password' => $this->validated(new SessionNewPasswordForm, $data)])->save();
            // Mot de passe oublié : les autres appareils sont déconnectés, comme au mot de passe oublié d'hier.
            $user->tokens()->delete();
        } else {
            $this->check($verified, $user, $this->validated(new SessionPasswordForm, $data));
        }

        $this->codeService->close($verified->challenge);

        return ['user' => $user, 'token' => Challenges::token($user, $deviceName)];
    }

    /**
     * Le mot de passe saisi, validé par les règles du formulaire de l'étape.
     *
     * @param  array<string, mixed>  $data
     *
     * @throws ValidationException
     */
    private function validated(Form $form, array $data): string
    {
        return (string) Validator::make($data, $form->rules(), [], $form->attributes())->validate()['password'];
    }

    /**
     * @throws ValidationException mot de passe faux ; au cinquième essai, le défi est clos
     */
    private function check(VerificationCode $verified, User $user, string $password): void
    {
        if (Hash::check($password, (string) $user->password)) {
            return;
        }

        $verified->increment('attempts');

        if ($verified->attempts >= $this->codeService->maxAttempts()) {
            $this->codeService->close($verified->challenge);

            throw ValidationException::withMessages(['password' => __('auth::form.session.too_many')]);
        }

        throw ValidationException::withMessages(['password' => __('auth::form.session.wrong_password')]);
    }
}
