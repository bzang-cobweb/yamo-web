<?php

namespace Modules\Auth\Http\Controllers\Api;

use Illuminate\Http\Response;
use Modules\Auth\Http\Requests\IdentityRequest;
use Modules\Auth\Models\User;
use Modules\Auth\Services\IdentityDocumentService;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/** Envoyer ses documents d'identité (décision 2026-11-25-identification). */
class IdentityController
{
    public function update(IdentityRequest $request, IdentityDocumentService $documents): Response
    {
        /** @var User $user */
        $user = $request->user();
        $documents->submit($user, $request->validated());

        return response()->noContent();
    }

    /** Une photo envoyée, par son lien signé (l'aperçu du champ dans l'app), jamais sans lui. */
    public function show(string $uuid): BinaryFileResponse
    {
        $media = Media::query()->where('uuid', $uuid)->where('collection_name', IdentityDocumentService::COLLECTION)->firstOrFail();

        return response()->file($media->getPath(), ['Cache-Control' => 'private, no-store']);
    }
}
