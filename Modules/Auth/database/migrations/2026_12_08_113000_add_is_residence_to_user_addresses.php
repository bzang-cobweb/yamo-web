<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * L'adresse de résidence du compte (décision 2026-11-27-adresse-de-residence) : une adresse du compte marquée, une
 * seule par compte, hors de la liste « Mes adresses ».
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_addresses', function (Blueprint $table) {
            $table->boolean('is_residence')->default(false)->after('indication');
        });
    }

    public function down(): void
    {
        Schema::table('user_addresses', function (Blueprint $table) {
            $table->dropColumn('is_residence');
        });
    }
};
