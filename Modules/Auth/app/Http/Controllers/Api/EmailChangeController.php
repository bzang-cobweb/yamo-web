<?php

namespace Modules\Auth\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Modules\Auth\Http\Requests\EmailChangeRequest;
use Modules\Auth\Models\User;
use Modules\Auth\Services\EmailChangeService;

/**
 * Ajouter ou changer son e-mail (formulaire `auth.profile.email`).
 */
class EmailChangeController
{
    /**
     * Un code part vers la nouvelle adresse ; elle n'est enregistrée qu'une fois le
     * code validé par `POST /auth/codes/verify`.
     *
     * @response 202 array{challenge: string, channel: 'email', destination: string}
     */
    public function store(EmailChangeRequest $request, EmailChangeService $service): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        return response()->json($service->start($user, $request->validated()), 202);
    }
}
