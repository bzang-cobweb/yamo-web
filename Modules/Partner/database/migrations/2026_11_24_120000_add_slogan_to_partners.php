<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Le slogan d'un partenaire, traduit et facultatif (décision 2026-11-24-partenaire-slogan).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('partners', function (Blueprint $table): void {
            $table->json('slogan')->nullable()->after('name');
        });
    }

    public function down(): void
    {
        Schema::table('partners', function (Blueprint $table): void {
            $table->dropColumn('slogan');
        });
    }
};
