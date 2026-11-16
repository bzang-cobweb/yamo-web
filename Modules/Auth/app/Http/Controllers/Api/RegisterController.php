<?php

namespace Modules\Auth\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Modules\Auth\Http\Requests\RegisterRequest;
use Modules\Auth\Services\RegisterService;

/**
 * Créer un compte depuis l'app (formulaire `auth.register`).
 */
class RegisterController
{
    /**
     * Inscription : un code part vers l'e-mail et/ou le téléphone saisis. Le compte
     * n'est créé qu'une fois tous les codes validés par `POST /auth/codes/verify`.
     *
     * @unauthenticated
     *
     * @response 202 array{challenge: string, channel: 'email'|'sms', destination: string}
     */
    public function register(RegisterRequest $request, RegisterService $service): JsonResponse
    {
        return response()->json($service->start($request->validated()), 202);
    }
}
