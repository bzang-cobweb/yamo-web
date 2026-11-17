<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * Le logo n'est plus un réglage du back-office : ses fichiers sont livrés avec le
 * code. Supprime les logos déjà envoyés (ligne `media` et fichier sur le disque).
 */
return new class extends Migration
{
    public function up(): void
    {
        Media::query()
            ->where('model_type', 'App\Models\Setting')
            ->where('collection_name', 'logo')
            ->each(fn (Media $media) => $media->delete());
    }

    public function down(): void
    {
        // Rien à restaurer : les fichiers supprimés ne reviennent pas.
    }
};
