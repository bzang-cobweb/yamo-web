<?php

namespace Modules\Auth\Http\Controllers\Api;

use Modules\Auth\Http\Requests\UpdateReviewPrivacyRequest;
use Modules\Auth\Http\Resources\UserResource;
use Modules\Auth\Models\User;
use Modules\Auth\Services\PrivacySettingsService;

/**
 * Lieu sous mes avis de l'utilisateur connecté (formulaire `ReviewPrivacyForm`, décision 2026-11-27-confidentialite).
 */
class ReviewPrivacyController
{
    public function update(UpdateReviewPrivacyRequest $request, PrivacySettingsService $service): UserResource
    {
        /** @var User $user */
        $user = $request->user();

        return new UserResource($service->update($user, $request->validated()));
    }
}
