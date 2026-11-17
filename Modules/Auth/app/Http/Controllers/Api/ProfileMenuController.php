<?php

namespace Modules\Auth\Http\Controllers\Api;

use Illuminate\Http\Request;
use Modules\Auth\Models\User;
use Modules\Auth\Services\ProfileMenuService;

/**
 * Menu du profil de l'utilisateur connecté, défini par `ProfileMenuService`.
 */
class ProfileMenuController
{
    /**
     * Liens du menu du profil, dans l'ordre, chacun avec sa clé, son libellé dans
     * la langue du compte, et le nom du formulaire (`form`) ou de la liste
     * (`list`) qu'il ouvre dans l'app.
     *
     * @response array{data: list<array{key: 'personal'|'email'|'phone'|'password'|'preferences'|'addresses', label: string, form?: string, list?: string}>}
     */
    public function index(Request $request, ProfileMenuService $service): array
    {
        /** @var User $user */
        $user = $request->user();

        return ['data' => $service->for($user)->all()];
    }
}
