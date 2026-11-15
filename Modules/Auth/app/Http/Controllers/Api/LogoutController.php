<?php

namespace Modules\Auth\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Modules\Auth\Models\User;
use Modules\Auth\Services\LogoutService;

/**
 * Déconnexion de l'application mobile.
 */
class LogoutController
{
    /**
     * Déconnexion : révoque le jeton utilisé par la requête.
     */
    public function logout(Request $request, LogoutService $service): Response
    {
        /** @var User $user */
        $user = $request->user();
        $service->logout($user);

        return response()->noContent();
    }
}
