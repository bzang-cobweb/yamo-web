<?php

namespace Modules\Auth\Http\Controllers\Api;

use Illuminate\Http\Request;
use Modules\Auth\Models\User;
use Modules\Auth\Services\ProfileMenuService;

/**
 * Menu du profil de l'utilisateur connecté, réglé dans le back-office.
 */
class ProfileMenuController
{
    /**
     * Liens du menu du profil, dans l'ordre, chacun avec la clé de l'écran qu'il
     * ouvre et son libellé dans la langue du compte.
     *
     * @response array{data: list<array{key: 'personal'|'email'|'phone'|'password'|'preferences', label: string}>}
     */
    public function index(Request $request, ProfileMenuService $service): array
    {
        /** @var User $user */
        $user = $request->user();

        return ['data' => $service->for($user)->all()];
    }
}
