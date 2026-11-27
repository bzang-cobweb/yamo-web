<?php

namespace Modules\Auth\Http\Controllers\Api;

use Modules\Auth\Http\Requests\UpdateNotificationSettingsRequest;
use Modules\Auth\Http\Resources\UserResource;
use Modules\Auth\Models\User;
use Modules\Auth\Services\NotificationSettingsService;

/**
 * Choix de notifications de l'utilisateur connecté (formulaire `auth.profile.notifications`).
 */
class NotificationSettingsController
{
    public function update(UpdateNotificationSettingsRequest $request, NotificationSettingsService $service): UserResource
    {
        /** @var User $user */
        $user = $request->user();

        return new UserResource($service->update($user, $request->validated()));
    }
}
