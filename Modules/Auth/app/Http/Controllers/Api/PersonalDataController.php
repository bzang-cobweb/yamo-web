<?php

namespace Modules\Auth\Http\Controllers\Api;

use Illuminate\Http\Response;
use Modules\Auth\Http\Requests\PersonalDataRequest;
use Modules\Auth\Models\User;
use Modules\Auth\Services\PersonalDataService;

/**
 * « Mes données » (formulaire `auth.profile.privacy.data`, décision 2026-11-27-confidentialite).
 */
class PersonalDataController
{
    /**
     * Les données du compte, en JSON, partent par e-mail à son adresse vérifiée (204). Sans adresse vérifiée : 422,
     * erreur sur `email`. Trois demandes par heure au plus.
     */
    public function store(PersonalDataRequest $request, PersonalDataService $service): Response
    {
        /** @var User $user */
        $user = $request->user();

        $service->send($user);

        return response()->noContent();
    }
}
