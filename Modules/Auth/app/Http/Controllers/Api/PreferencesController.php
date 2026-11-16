<?php

namespace Modules\Auth\Http\Controllers\Api;

use Modules\Auth\Http\Requests\UpdatePreferencesRequest;
use Modules\Auth\Http\Resources\UserResource;
use Modules\Auth\Models\User;
use Modules\Auth\Services\PreferencesService;

/**
 * Préférences de l'utilisateur connecté (formulaire `auth.profile.preferences`).
 */
class PreferencesController
{
    /**
     * Modifier ses préférences (langue).
     */
    public function update(UpdatePreferencesRequest $request, PreferencesService $service): UserResource
    {
        /** @var User $user */
        $user = $request->user();

        return new UserResource($service->update($user, $request->validated()));
    }
}
