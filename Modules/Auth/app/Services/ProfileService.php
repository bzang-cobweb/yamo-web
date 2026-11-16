<?php

namespace Modules\Auth\Services;

use Illuminate\Http\UploadedFile;
use Modules\Auth\Models\User;

/**
 * Profil de l'utilisateur connecté.
 */
final class ProfileService
{
    public function __construct(private readonly AvatarService $avatars) {}

    /**
     * Remplace sa photo de profil par le fichier envoyé, optimisé avant
     * enregistrement.
     */
    public function updateAvatar(User $user, UploadedFile $file): User
    {
        $this->avatars->store($user, $file->getRealPath());

        return $user;
    }

    /**
     * Retire sa photo de profil.
     */
    public function removeAvatar(User $user): User
    {
        $this->avatars->remove($user);

        return $user;
    }
}
