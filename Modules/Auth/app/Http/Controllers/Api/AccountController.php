<?php

namespace Modules\Auth\Http\Controllers\Api;

use Illuminate\Http\Response;
use Modules\Auth\Http\Requests\DeleteAccountRequest;
use Modules\Auth\Http\Requests\RestoreAccountRequest;
use Modules\Auth\Http\Resources\UserResource;
use Modules\Auth\Models\User;
use Modules\Auth\Services\AccountDeletionService;

/**
 * Compte de l'utilisateur : le supprimer (formulaire `auth.profile.delete-account`),
 * puis le récupérer pendant le délai avant la purge.
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

    /**
     * Récupérer son compte en attente de suppression, depuis l'app : après une
     * connexion dont le code a été validé (réponse `{next: "restore"}`). Le compte
     * est restauré et l'appareil connecté. Un défi inconnu, expiré ou visant un
     * compte actif est refusé (422, erreur sur `code`).
     *
     * @unauthenticated
     */
    public function restore(RestoreAccountRequest $request, AccountDeletionService $service): UserResource
    {
        $result = $service->restore((string) $request->validated('challenge'), (string) ($request->validated('device_name') ?: 'app'));

        return (new UserResource($result['user']))->additional([
            /** Jeton Sanctum de l'appareil. */
            'token' => $result['token'],
        ]);
    }
}
