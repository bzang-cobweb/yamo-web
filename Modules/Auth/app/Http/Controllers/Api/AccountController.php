<?php

namespace Modules\Auth\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
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
     * Demander la suppression de son compte, avec sa raison (obligatoire) : un code part à l'e-mail vérifié, sinon au
     * numéro vérifié (202). Sa validation par `POST /auth/codes/verify` désactive le compte et déconnecte tous ses
     * appareils ; il est purgé après le délai réglé (`AUTH_ACCOUNT_DELETION_GRACE_DAYS`). Le dernier super
     * administrateur est refusé (422).
     *
     * @response 202 array{challenge: string, channel: 'email'|'sms', destination: string}
     */
    public function destroy(DeleteAccountRequest $request, AccountDeletionService $service): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        return response()->json($service->start($user, (string) $request->validated('reason')), 202);
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
