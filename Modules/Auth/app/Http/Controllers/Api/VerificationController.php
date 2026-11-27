<?php

namespace Modules\Auth\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Modules\Auth\Enums\VerificationChannel;
use Modules\Auth\Enums\VerificationPurpose;
use Modules\Auth\Http\Requests\ResendCodeRequest;
use Modules\Auth\Http\Requests\VerifyCodeRequest;
use Modules\Auth\Http\Resources\UserResource;
use Modules\Auth\Models\User;
use Modules\Auth\Services\AccountDeletionService;
use Modules\Auth\Services\ContactVerificationService;
use Modules\Auth\Services\EmailChangeService;
use Modules\Auth\Services\LoginService;
use Modules\Auth\Services\PasswordResetService;
use Modules\Auth\Services\PhoneChangeService;
use Modules\Auth\Services\RegisterService;
use Modules\Auth\Services\UserPhoneService;
use Modules\Auth\Services\VerificationCodeService;

/**
 * Codes de vérification (formulaire `auth.code`) : connexion, inscription,
 * changement ou vérification d'e-mail ou de téléphone, suppression du compte.
 */
class VerificationController
{
    /**
     * Valider un code. Selon l'opération du défi :
     * - connexion : l'utilisateur et son jeton (`token`) ;
     * - inscription dont tous les codes sont validés : 201, le compte créé et son jeton ;
     * - inscription avec un autre code attendu : 202 et le prochain code à saisir ;
     * - changement d'e-mail ou de téléphone (connecté) : l'utilisateur à jour ;
     * - ajout d'un numéro supplémentaire (connecté) : l'utilisateur ;
     * - vérification de l'e-mail ou du téléphone actuel (connecté) : l'utilisateur à jour ;
     * - suppression du compte (connecté) : le compte supprimé (l'app déconnecte) ;
     * - mot de passe oublié : `{next: "password", challenge}`, puis
     *   `POST /auth/password/reset`.
     *
     * @unauthenticated
     */
    public function verify(
        VerifyCodeRequest $request,
        VerificationCodeService $codeService,
        LoginService $login,
        RegisterService $register,
        EmailChangeService $emailChange,
        PhoneChangeService $phoneChange,
        PasswordResetService $passwordReset,
        ContactVerificationService $contactVerification,
        AccountDeletionService $accountDeletion,
        UserPhoneService $userPhones,
    ): UserResource|JsonResponse {
        /** @var User|null $actingUser */
        $actingUser = $request->user('sanctum');
        $channel = $request->filled('channel') ? VerificationChannel::from((string) $request->input('channel')) : null;
        $deviceName = (string) ($request->input('device_name') ?: 'app');

        $verified = $codeService->verify((string) $request->input('challenge'), $channel, (string) $request->input('code'), $actingUser);

        $result = match ($verified->purpose) {
            VerificationPurpose::LOGIN => $login->complete($verified, $deviceName),
            VerificationPurpose::REGISTER => $register->complete($verified, $deviceName),
            VerificationPurpose::CHANGE_EMAIL => $emailChange->complete($verified),
            VerificationPurpose::CHANGE_PHONE => $phoneChange->complete($verified),
            VerificationPurpose::ADD_PHONE => $userPhones->complete($verified),
            VerificationPurpose::RESET_PASSWORD => $passwordReset->confirm($verified),
            VerificationPurpose::VERIFY_EMAIL, VerificationPurpose::VERIFY_PHONE => $contactVerification->complete($verified),
            VerificationPurpose::DELETE_ACCOUNT => $accountDeletion->complete($verified),
        };

        if ($result instanceof User) {
            return new UserResource($result);
        }

        if (isset($result['next'])) {
            return response()->json($result);
        }

        if (! isset($result['token'])) {
            return response()->json($result, 202);
        }

        return (new UserResource($result['user']))->additional([
            /** Jeton Sanctum de l'appareil. */
            'token' => $result['token'],
        ]);
    }

    /**
     * Renvoyer les codes encore attendus d'un défi (60 secondes au moins après le
     * dernier envoi).
     *
     * @unauthenticated
     */
    public function resend(ResendCodeRequest $request, VerificationCodeService $codeService): Response
    {
        /** @var User|null $actingUser */
        $actingUser = $request->user('sanctum');
        $codeService->resend((string) $request->input('challenge'), $actingUser);

        return response()->noContent();
    }
}
