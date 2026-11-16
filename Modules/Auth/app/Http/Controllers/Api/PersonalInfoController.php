<?php

namespace Modules\Auth\Http\Controllers\Api;

use Modules\Auth\Http\Requests\UpdatePersonalInfoRequest;
use Modules\Auth\Http\Resources\UserResource;
use Modules\Auth\Models\User;
use Modules\Auth\Services\PersonalInfoService;

/**
 * Informations personnelles de l'utilisateur connecté (formulaire `auth.profile.personal`).
 */
class PersonalInfoController
{
    /**
     * Modifier son prénom et son nom.
     */
    public function update(UpdatePersonalInfoRequest $request, PersonalInfoService $service): UserResource
    {
        /** @var User $user */
        $user = $request->user();

        return new UserResource($service->update($user, $request->validated()));
    }
}
