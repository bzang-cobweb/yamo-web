<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Une offre remontée en tête du carrousel de sa région jusqu'à ce jour inclus (décision 2026-11-25-remontee-carrousel).
 * Vide : pas remontée.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('offers', function (Blueprint $table) {
            $table->date('boosted_until')->nullable()->index()->after('featured_until');
        });
    }

    public function down(): void
    {
        Schema::table('offers', function (Blueprint $table) {
            $table->dropIndex(['boosted_until']);
            $table->dropColumn('boosted_until');
        });
    }
};
