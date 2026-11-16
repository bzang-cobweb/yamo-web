<?php

namespace Modules\Auth\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Modules\Auth\Http\Requests\ForgotPasswordRequest;
use Modules\Auth\Http\Requests\ResetPasswordRequest;
use Modules\Auth\Services\PasswordResetService;

/**
 * Mot de passe oublié (formulaires `auth.password.forgot` puis `auth.password.reset`).
 */
class PasswordResetController
{
    /**
     * E-mail ou téléphone (format international) : un code y part si un compte
     * correspond. La réponse est la même qu'un compte existe ou non. Le code se valide
     * par `POST /auth/codes/verify`, qui répond `{next: "password", challenge}`.
     *
     * @unauthenticated
     *
     * @response 202 array{challenge: string, channel: 'email'|'sms', destination: string}
     */
    public function forgot(ForgotPasswordRequest $request, PasswordResetService $service): JsonResponse
    {
        return response()->json($service->start($request->validated()), 202);
    }

    /**
     * Nouveau mot de passe, pour un défi dont le code vient d'être validé. Toutes les
     * sessions de l'app sont fermées ; on se reconnecte avec le nouveau mot de passe.
     *
     * @unauthenticated
     */
    public function reset(ResetPasswordRequest $request, PasswordResetService $service): Response
    {
        $service->reset((string) $request->input('challenge'), (string) $request->input('password'));

        return response()->noContent();
    }
}
