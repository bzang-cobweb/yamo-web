<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Indications sur l'adresse propres à l'offre (décision 2026-11-19-description-adresse-offre) :
 * une adresse est partagée, sa description ne l'est pas. Texte facultatif, non traduit.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('offers', function (Blueprint $table) {
            $table->string('address_description', 500)->nullable()->after('address_id');
        });
    }

    public function down(): void
    {
        Schema::table('offers', function (Blueprint $table) {
            $table->dropColumn('address_description');
        });
    }
};
