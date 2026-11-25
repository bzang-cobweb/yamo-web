<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Un partenaire en vedette jusqu'à ce jour inclus, comme une offre (décisions 2026-11-25-vedettes-d-abord et
 * 2026-11-25-vedette-en-dates). Vide : pas en vedette.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('partners', function (Blueprint $table) {
            $table->date('featured_until')->nullable()->after('expires_at');
        });
    }

    public function down(): void
    {
        Schema::table('partners', function (Blueprint $table) {
            $table->dropColumn('featured_until');
        });
    }
};
