<?php

namespace Modules\Auth\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Modules\Auth\Enums\VerificationChannel;
use Modules\Auth\Http\Requests\PhoneVerifyRequest;
use Modules\Auth\Models\User;
use Modules\Auth\Services\ContactVerificationService;

/**
 * Vérifier son numéro actuel (formulaire `auth.profile.phone.verify`, décision 2026-11-27-verifier-email-telephone).
 */
class PhoneVerificationController
{
    /**
     * Un code part vers la valeur actuelle du compte ; sa validation par `POST /auth/codes/verify` la marque vérifiée.
     * Rien à vérifier, ou déjà vérifié : 422.
     *
     * @response 202 array{challenge: string, channel: 'sms', destination: string}
     */
    public function store(PhoneVerifyRequest $request, ContactVerificationService $service): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        return response()->json($service->start($user, VerificationChannel::SMS), 202);
    }
}
