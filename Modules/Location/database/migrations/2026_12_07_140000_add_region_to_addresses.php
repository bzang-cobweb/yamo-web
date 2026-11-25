<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * La région d'une adresse (décision 2026-11-25-region-de-l-adresse). Obligatoire à la saisie ; la colonne accepte
 * le vide pour les adresses déjà enregistrées qu'aucune règle ne rattache encore à une région.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('addresses', function (Blueprint $table) {
            $table->foreignId('region_id')->nullable()->after('city')->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('addresses', function (Blueprint $table) {
            $table->dropConstrainedForeignId('region_id');
        });
    }
};
