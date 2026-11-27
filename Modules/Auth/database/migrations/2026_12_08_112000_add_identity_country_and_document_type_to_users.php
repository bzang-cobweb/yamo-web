<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * La pièce d'identité envoyée (décision 2026-11-27-identite-pays-et-type-de-piece) : le pays qui l'a délivrée et son
 * type (permis de conduire, passeport, carte d'identité). Vides tant que rien n'est envoyé.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('identity_country_id')->nullable()->constrained('countries')->nullOnDelete();
            $table->string('identity_document_type', 32)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('identity_country_id');
            $table->dropColumn('identity_document_type');
        });
    }
};
