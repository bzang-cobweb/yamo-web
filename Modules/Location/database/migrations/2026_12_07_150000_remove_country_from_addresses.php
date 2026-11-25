<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Une adresse n'a plus de pays à elle : c'est celui de sa région, devenue obligatoire (décision
 * 2026-11-25-adresse-sans-pays). L'unicité passe du pays à la région ; supprimer une région, ou son pays, supprime
 * ses adresses, comme le pays avant. S'il reste une adresse sans région, la migration s'arrête sans rien changer : son pays serait perdu.
 */
return new class extends Migration
{
    public function up(): void
    {
        $orphans = DB::table('addresses')->whereNull('region_id')->count();
        if ($orphans > 0) {
            throw new RuntimeException("{$orphans} adresse(s) sans région : leur donner une région avant d'enlever le pays des adresses.");
        }

        // La clé étrangère d'abord : MariaDB garde l'index unique tant qu'elle s'en sert.
        Schema::table('addresses', function (Blueprint $table) {
            $table->dropForeign(['country_id']);
            $table->dropForeign(['region_id']);
        });
        Schema::table('addresses', function (Blueprint $table) {
            $table->dropUnique(['country_id', 'fingerprint']);
            $table->dropColumn('country_id');
        });
        Schema::table('addresses', function (Blueprint $table) {
            $table->unsignedBigInteger('region_id')->nullable(false)->change();
            // Comme avant avec le pays : supprimer une région (son pays) supprime ses adresses.
            $table->foreign('region_id')->references('id')->on('regions')->cascadeOnDelete();
        });
        Schema::table('addresses', function (Blueprint $table) {
            $table->unique(['region_id', 'fingerprint']);
        });
    }

    public function down(): void
    {
        Schema::table('addresses', function (Blueprint $table) {
            $table->dropForeign(['region_id']);
        });
        Schema::table('addresses', function (Blueprint $table) {
            $table->dropUnique(['region_id', 'fingerprint']);
            $table->foreignId('country_id')->nullable()->after('id')->constrained()->cascadeOnDelete();
        });
        // Le pays de chaque adresse : celui de sa région.
        foreach (DB::table('regions')->get(['id', 'country_id']) as $region) {
            DB::table('addresses')->where('region_id', $region->id)->update(['country_id' => $region->country_id]);
        }
        Schema::table('addresses', function (Blueprint $table) {
            $table->unsignedBigInteger('region_id')->nullable()->change();
            $table->foreign('region_id')->references('id')->on('regions')->nullOnDelete();
        });
        Schema::table('addresses', function (Blueprint $table) {
            $table->unique(['country_id', 'fingerprint']);
        });
    }
};
