<?php

namespace Modules\Auth\Http\Controllers\Api;

use Illuminate\Http\Response;
use Modules\Auth\Http\Requests\UpdatePasswordRequest;
use Modules\Auth\Models\User;
use Modules\Auth\Services\PasswordService;

/**
 * Mot de passe de l'utilisateur connecté (formulaire `auth.profile.password`).
 */
class PasswordController
{
    /**
     * Changer son mot de passe. Les autres appareils sont déconnectés ; celui-ci
     * reste connecté.
     */
    public function update(UpdatePasswordRequest $request, PasswordService $service): Response
    {
        /** @var User $user */
        $user = $request->user();

        $service->update($user, $request->validated('password'));

        return response()->noContent();
    }
}
