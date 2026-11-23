<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Le début de la période payée d'une offre, en face de `expires_at` (décision 2026-11-23-offre-paye-a-partir-du) :
 * le paiement appliqué, une offre n'est visible des autres qu'entre les deux dates.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('offers', function (Blueprint $table) {
            $table->timestamp('starts_at')->nullable()->after('featured_until');
        });
    }

    public function down(): void
    {
        Schema::table('offers', function (Blueprint $table) {
            $table->dropColumn('starts_at');
        });
    }
};
