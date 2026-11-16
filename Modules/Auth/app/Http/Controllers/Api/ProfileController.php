<?php

namespace Modules\Auth\Http\Controllers\Api;

use Illuminate\Http\Request;
use Modules\Auth\Http\Requests\UpdateAvatarRequest;
use Modules\Auth\Http\Requests\UpdateMeRequest;
use Modules\Auth\Http\Resources\UserResource;
use Modules\Auth\Models\User;
use Modules\Auth\Services\ProfileService;

/**
 * Profil de l'utilisateur connecté.
 */
class ProfileController
{
    /**
     * Utilisateur connecté.
     */
    public function show(Request $request): UserResource
    {
        return new UserResource($request->user());
    }

    /**
     * Modifier son propre compte (aujourd'hui : la langue). Les champs non prévus
     * sont ignorés.
     */
    public function update(UpdateMeRequest $request, ProfileService $service): UserResource
    {
        /** @var User $user */
        $user = $request->user();

        return new UserResource($service->update($user, $request->validated()));
    }

    /**
     * Remplacer sa photo de profil (multipart, champ `avatar` : JPEG, PNG ou WebP,
     * 5 Mo au plus). L'image est optimisée avant d'être enregistrée.
     */
    public function updateAvatar(UpdateAvatarRequest $request, ProfileService $service): UserResource
    {
        /** @var User $user */
        $user = $request->user();

        return new UserResource($service->updateAvatar($user, $request->file('avatar')));
    }

    /**
     * Retirer sa photo de profil.
     */
    public function destroyAvatar(Request $request, ProfileService $service): UserResource
    {
        /** @var User $user */
        $user = $request->user();

        return new UserResource($service->removeAvatar($user));
    }
}
