<?php

namespace Modules\Offer\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Auth\Models\User;
use Modules\Offer\Services\MemberProfile;

/**
 * La page publique d'un membre, pour un compte connecté (décision 2026-11-19-page-membre).
 */
class MemberController
{
    public function __construct(private readonly MemberProfile $profile) {}

    /**
     * Sa photo, son nom, « Membre depuis », sa présentation, sa ville et ses séjours.
     */
    public function show(Request $request, User $member): JsonResponse
    {
        $viewer = $request->user();
        $locale = $viewer instanceof User ? $viewer->preferredLocale() : app()->getLocale();

        return response()->json(['data' => $this->profile->show($member, $locale)]);
    }
}
