<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Le pays d'une offre est obligatoire jusque dans la base (décision
 * 2026-11-19-pays-obligatoire-offre) : la colonne n'accepte plus de vide, et un
 * pays qui a des offres ne peut plus être supprimé (il les vidait de leur pays).
 *
 * Sous SQLite, changer la colonne reconstruit la table : les clés étrangères sont
 * coupées le temps de l'opération, sinon les lignes qui visent une offre
 * (adresse, informations, avis…) seraient supprimées en cascade.
 */
return new class extends Migration
{
    public function up(): void
    {
        $missing = DB::table('offers')->whereNull('country_id')->count();
        if ($missing > 0) {
            throw new RuntimeException("{$missing} offre(s) sans pays : leur en donner un avant cette migration.");
        }

        Schema::withoutForeignKeyConstraints(function (): void {
            Schema::table('offers', function (Blueprint $table) {
                $table->dropForeign(['country_id']);
            });
            Schema::table('offers', function (Blueprint $table) {
                $table->foreignId('country_id')->nullable(false)->change();
                $table->foreign('country_id')->references('id')->on('countries')->restrictOnDelete();
            });
        });
    }

    public function down(): void
    {
        Schema::withoutForeignKeyConstraints(function (): void {
            Schema::table('offers', function (Blueprint $table) {
                $table->dropForeign(['country_id']);
            });
            Schema::table('offers', function (Blueprint $table) {
                $table->foreignId('country_id')->nullable()->change();
                $table->foreign('country_id')->references('id')->on('countries')->nullOnDelete();
            });
        });
    }
};
