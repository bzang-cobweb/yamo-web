<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Âge minimum (décision 2026-11-19-age-minimum) : renseigné, la date de naissance
 * devient obligatoire à l'inscription et dans les informations personnelles, et
 * doit donner au moins cet âge.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->unsignedTinyInteger('minimum_age')->nullable()->after('payment_required');
        });
    }

    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->dropColumn('minimum_age');
        });
    }
};
