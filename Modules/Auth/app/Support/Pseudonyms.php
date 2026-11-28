<?php

namespace Modules\Auth\Support;

use Illuminate\Support\Str;
use Modules\Auth\Models\User;

/**
 * Pseudonyme (`users.name`, unique) d'un compte inscrit depuis l'app : l'inscription ne le demande plus, il est tiré du
 * prénom et du nom (décision 2026-11-28-connexion-panneau).
 */
final class Pseudonyms
{
    /** Longueur de la base, avant le suffixe numérique. */
    private const BASE_LENGTH = 30;

    /**
     * Prénom et nom en minuscules, sans accent ni espace ni signe (« Jeanne d'Arc » → « jeannedarc ») ; le premier
     * libre parmi la base, puis la base suivie de 2, 3… Un compte en attente de suppression garde le sien.
     */
    public static function generate(string $firstName, string $lastName): string
    {
        $base = Str::of($firstName.$lastName)->ascii()->lower()->replaceMatches('/[^a-z0-9]/', '')->limit(self::BASE_LENGTH, '')->toString();
        $base = $base === '' ? 'membre' : $base;

        $taken = User::withTrashed()->where('name', 'like', $base.'%')->pluck('name')->flip();

        if (! $taken->has($base)) {
            return $base;
        }

        $suffix = 2;
        while ($taken->has($base.$suffix)) {
            $suffix++;
        }

        return $base.$suffix;
    }
}
