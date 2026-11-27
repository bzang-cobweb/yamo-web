<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Les réglages de confidentialité du compte (décision 2026-11-27-confidentialite) : qui voit sa page publique, son
 * lieu sous ses avis. Vide : les défauts (tout le monde, sans lieu).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->json('privacy_settings')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('privacy_settings');
        });
    }
};
