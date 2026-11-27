<?php

namespace Modules\Auth\Http\Controllers\Api;

use Modules\Auth\Http\Requests\UpdateProfileVisibilityRequest;
use Modules\Auth\Http\Resources\UserResource;
use Modules\Auth\Models\User;
use Modules\Auth\Services\PrivacySettingsService;

/**
 * Visibilité du profil de l'utilisateur connecté (formulaire `ProfileVisibilityForm`, décision 2026-11-27-confidentialite).
 */
class ProfileVisibilityController
{
    public function update(UpdateProfileVisibilityRequest $request, PrivacySettingsService $service): UserResource
    {
        /** @var User $user */
        $user = $request->user();

        return new UserResource($service->update($user, $request->validated()));
    }
}
