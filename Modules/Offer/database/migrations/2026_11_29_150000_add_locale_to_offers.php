<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * La langue d'origine d'une offre (décision 2026-11-19-contrat-langue-saisie-allemand) :
 * celle de son auteur à la création ; un texte manquant s'affiche dans cette langue.
 * Offres existantes : le français si leur titre l'a, sinon la première langue du titre.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('offers', function (Blueprint $table) {
            $table->string('locale', 8)->nullable()->after('country_id');
        });

        DB::table('offers')->orderBy('id')->each(function (object $offer): void {
            $titles = array_keys(array_filter((array) json_decode((string) $offer->title, true), fn (mixed $title): bool => is_string($title) && trim($title) !== ''));
            $locale = in_array('fr', $titles, true) ? 'fr' : ($titles[0] ?? null);

            DB::table('offers')->where('id', $offer->id)->update(['locale' => $locale]);
        });
    }

    public function down(): void
    {
        Schema::table('offers', function (Blueprint $table) {
            $table->dropColumn('locale');
        });
    }
};
