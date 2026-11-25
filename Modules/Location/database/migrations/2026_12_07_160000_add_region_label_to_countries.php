<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Le nom des régions d'un pays, traduit (« Région », « Canton », « District », « État » ; décision
 * 2026-11-25-regions-par-pays). Vide : « Région ».
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('countries', function (Blueprint $table) {
            $table->json('region_label')->nullable()->after('native_name');
        });
    }

    public function down(): void
    {
        Schema::table('countries', function (Blueprint $table) {
            $table->dropColumn('region_label');
        });
    }
};
