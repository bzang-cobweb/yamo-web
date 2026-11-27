<?php

use Bza\Form\Support\Locales;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Titre et description des offres traduisibles (décision
 * 2026-11-18-offres-multilingues-categories-arbre) : le texte existant est rangé
 * sous la langue par défaut. Retour en arrière : le texte de la langue par
 * défaut, à défaut le premier disponible.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('offers', function (Blueprint $table) {
            $table->json('title_translations')->nullable();
            $table->json('description_translations')->nullable();
        });

        $locale = Locales::default();

        DB::table('offers')->orderBy('id')->each(function (object $offer) use ($locale): void {
            DB::table('offers')->where('id', $offer->id)->update([
                'title_translations' => json_encode([$locale => $offer->title], JSON_UNESCAPED_UNICODE),
                'description_translations' => $offer->description === null ? null : json_encode([$locale => $offer->description], JSON_UNESCAPED_UNICODE),
            ]);
        });

        Schema::table('offers', function (Blueprint $table) {
            $table->dropColumn(['title', 'description']);
        });
        Schema::table('offers', function (Blueprint $table) {
            $table->renameColumn('title_translations', 'title');
            $table->renameColumn('description_translations', 'description');
        });
    }

    public function down(): void
    {
        Schema::table('offers', function (Blueprint $table) {
            $table->string('title_text')->default('');
            $table->text('description_text')->nullable();
        });

        $pick = function (?string $json): ?string {
            $translations = json_decode((string) $json, true);

            return is_array($translations) && $translations !== [] ? (string) ($translations[Locales::default()] ?? reset($translations)) : null;
        };

        DB::table('offers')->orderBy('id')->each(function (object $offer) use ($pick): void {
            DB::table('offers')->where('id', $offer->id)->update([
                'title_text' => mb_substr((string) $pick($offer->title), 0, 255),
                'description_text' => $pick($offer->description),
            ]);
        });

        Schema::table('offers', function (Blueprint $table) {
            $table->dropColumn(['title', 'description']);
        });
        Schema::table('offers', function (Blueprint $table) {
            $table->renameColumn('title_text', 'title');
            $table->renameColumn('description_text', 'description');
        });
    }
};
