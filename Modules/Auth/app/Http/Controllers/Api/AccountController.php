<?php

namespace Modules\Auth\Http\Controllers\Api;

use Illuminate\Http\Response;
use Modules\Auth\Http\Requests\DeleteAccountRequest;
use Modules\Auth\Models\User;
use Modules\Auth\Services\AccountDeletionService;

/**
 * Compte de l'utilisateur connecté (formulaire `auth.profile.delete-account`).
 */
class AccountController
{
    /**
     * Supprimer son compte, après confirmation par le mot de passe. Le compte
     * est désactivé et tous ses appareils déconnectés ; il est purgé après le
     * délai réglé (`AUTH_ACCOUNT_DELETION_GRACE_DAYS`). Le dernier super
     * administrateur est refusé (422).
     */
    public function destroy(DeleteAccountRequest $request, AccountDeletionService $service): Response
    {
        /** @var User $user */
        $user = $request->user();

        $service->delete($user);

        return response()->noContent();
    }
}
