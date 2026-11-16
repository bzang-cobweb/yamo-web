<?php

namespace Modules\Auth\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Modules\Auth\Http\Requests\PhoneChangeRequest;
use Modules\Auth\Models\User;
use Modules\Auth\Services\PhoneChangeService;

/**
 * Ajouter ou changer son téléphone (formulaire `auth.profile.phone`).
 */
class PhoneChangeController
{
    /**
     * Un code part par SMS vers le nouveau numéro ; il n'est enregistré qu'une fois
     * le code validé par `POST /auth/codes/verify`.
     *
     * @response 202 array{challenge: string, channel: 'sms', destination: string}
     */
    public function store(PhoneChangeRequest $request, PhoneChangeService $service): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        return response()->json($service->start($user, $request->validated()), 202);
    }
}
