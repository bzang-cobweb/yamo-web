<?php

namespace Modules\Auth\Http\Controllers\Api;

use Illuminate\Http\Response;
use Modules\Auth\Http\Requests\UpdateEmergencyContactRequest;
use Modules\Auth\Models\User;
use Modules\Auth\Services\EmergencyContactService;

/**
 * Le contact d'urgence de l'utilisateur connecté (formulaire `auth.profile.emergency-contact`, décision
 * 2026-11-27-contact-d-urgence).
 */
class EmergencyContactController
{
    /**
     * Le créer ou le modifier.
     */
    public function update(UpdateEmergencyContactRequest $request, EmergencyContactService $service): Response
    {
        /** @var User $user */
        $user = $request->user();
        $service->save($user, $request->validated());

        return response()->noContent();
    }
}
