<?php

namespace Modules\Auth\Http\Controllers\Api;

use Illuminate\Http\Response;
use Modules\Auth\Http\Requests\UpdateResidenceRequest;
use Modules\Auth\Models\User;
use Modules\Auth\Services\UserAddressService;

/**
 * L'adresse de résidence de l'utilisateur connecté (formulaire `auth.profile.residence`, décision
 * 2026-11-27-adresse-de-residence).
 */
class ResidenceController
{
    /**
     * La créer ou la modifier.
     */
    public function update(UpdateResidenceRequest $request, UserAddressService $service): Response
    {
        /** @var User $user */
        $user = $request->user();
        $service->saveResidence($user, $request->validated());

        return response()->noContent();
    }
}
