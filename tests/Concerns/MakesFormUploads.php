<?php

namespace Tests\Concerns;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Modules\Auth\Models\User;
use Bza\Form\Models\FormUpload;

/**
 * Un fichier en attente pour un champ `upload`, comme si l'app l'avait envoyé
 * (décision 2026-11-19-contrat-champ-upload-photos). À utiliser avec
 * `Storage::fake(FormUpload::DISK)`.
 */
trait MakesFormUploads
{
    protected function pendingUpload(User $user, string $field = 'photos', string $form = 'offer.offer', int $width = 800, int $height = 600): string
    {
        $uuid = (string) Str::uuid();
        $file = UploadedFile::fake()->image('photo.jpg', $width, $height);

        FormUpload::query()->create([
            'uuid' => $uuid,
            'user_id' => $user->getKey(),
            'form' => $form,
            'field' => $field,
            'path' => (string) $file->storeAs('form-uploads', $uuid, FormUpload::DISK),
            'mime_type' => 'image/jpeg',
            'size' => (int) $file->getSize(),
        ]);

        return $uuid;
    }
}
