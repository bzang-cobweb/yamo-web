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
     * (`list`) qu'il ouvre dans l'app, ou son sous-menu (`menu`), et son icône.
     *
     * @response array{data: list<array{key: string, label: string, icon: array{ios: string, android: string}|null, form?: string, list?: string, menu?: string}>}
     */
    public function index(Request $request, ProfileMenuService $service): array
    {
        /** @var User $user */
        $user = $request->user();

        return ['data' => $service->for($user)->all()];
    }

    /**
     * Un sous-menu du profil (« Informations personnelles », « Confidentialité » ; décision
     * 2026-11-27-menu-profil-icones-sous-menus) : son titre et ses liens, comme le menu.
     *
     * @response array{title: string, data: list<array{key: string, label: string, icon: array{ios: string, android: string}, form?: string, list?: string, menu?: string}>}
     */
    public function show(Request $request, ProfileMenuService $service, string $menu): array
    {
        /** @var User $user */
        $user = $request->user();

        return $service->submenu($user, $menu) ?? abort(404);
    }
}
