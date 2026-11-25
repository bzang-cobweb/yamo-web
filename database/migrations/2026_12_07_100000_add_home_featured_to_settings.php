<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Le bloc « À la une » de l'accueil (décision 2026-11-25-bloc-a-la-une) : combien d'offres, de 2 à 4. Vide : 2.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->unsignedTinyInteger('home_featured')->nullable()->after('home_partners');
        });
    }

    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->dropColumn('home_featured');
        });
    }
};
