<?php

namespace Modules\Auth\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Modules\Auth\Enums\VerificationChannel;
use Modules\Auth\Http\Requests\EmailVerifyRequest;
use Modules\Auth\Models\User;
use Modules\Auth\Services\ContactVerificationService;

/**
 * Vérifier son adresse e-mail actuelle (formulaire `auth.profile.email.verify`, décision 2026-11-27-verifier-email-telephone).
 */
class EmailVerificationController
{
    /**
     * Un code part vers la valeur actuelle du compte ; sa validation par `POST /auth/codes/verify` la marque vérifiée.
     * Rien à vérifier, ou déjà vérifié : 422.
     *
     * @response 202 array{challenge: string, channel: 'email', destination: string}
     */
    public function store(EmailVerifyRequest $request, ContactVerificationService $service): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        return response()->json($service->start($user, VerificationChannel::EMAIL), 202);
    }
}
