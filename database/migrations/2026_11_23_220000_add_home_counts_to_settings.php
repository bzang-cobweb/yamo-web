<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * L'accueil de l'app (décision 2026-11-23-accueil-nombres-reglables) : combien d'offres par ville dans les
 * carrousels, combien de partenaires dans le leur. Vides : 10.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->unsignedTinyInteger('home_offers_per_city')->nullable()->after('minimum_age');
            $table->unsignedTinyInteger('home_partners')->nullable()->after('home_offers_per_city');
        });
    }

    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->dropColumn(['home_offers_per_city', 'home_partners']);
        });
    }
};
