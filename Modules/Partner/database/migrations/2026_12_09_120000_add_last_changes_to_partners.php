<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * La dernière modification d'un partenaire depuis l'app : les champs changés, avant et après, qui l'a faite et quand
 * (décision 2026-11-26-partenaire-dernieres-modifications). Vide : jamais modifié depuis l'app.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('partners', function (Blueprint $table) {
            $table->json('last_changes')->nullable()->after('featured_until');
        });
    }

    public function down(): void
    {
        Schema::table('partners', function (Blueprint $table) {
            $table->dropColumn('last_changes');
        });
    }
};
